<?php

namespace App\Core\DataEngine\Services;

use App\Core\Audit\Services\AuditLogger;
use App\Core\DataEngine\Models\CoreDataTransferRun;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class DataTransferManager
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {
    }

    public function export(array $resource, Builder $query, ?User $actor = null): array
    {
        $fields = $this->exportableFields($resource);
        $records = $query->get();
        $csv = $this->buildCsv($records, $fields);

        $run = CoreDataTransferRun::query()->create([
            'requested_by' => $actor?->id,
            'resource_key' => $resource['key'],
            'source_module' => $resource['source_module'] ?? 'core-platform',
            'type' => 'export',
            'status' => 'completed',
            'file_name' => $this->exportFileName($resource['key']),
            'mime_type' => 'text/csv',
            'records_total' => $records->count(),
            'records_processed' => $records->count(),
            'records_failed' => 0,
            'metadata' => [
                'fields' => $fields->pluck('key')->all(),
            ],
            'finished_at' => now(),
        ]);

        $this->auditLogger->record(
            eventKey: 'data-resource.exported',
            actor: $actor,
            entityType: 'data-resource',
            entityKey: $resource['key'],
            summary: sprintf('Se exporto el recurso %s.', $resource['name'] ?? $resource['key']),
            sourceModule: $resource['source_module'] ?? 'core-platform',
            context: [
                'transfer_run_id' => $run->id,
                'records_total' => $records->count(),
                'fields' => $fields->pluck('key')->all(),
            ],
            organizationId: $this->tenantContext->organizationId($actor),
        );

        return [
            'run' => $run,
            'csv' => $csv,
            'file_name' => $run->file_name,
        ];
    }

    public function import(array $resource, UploadedFile $file, ?User $actor = null): CoreDataTransferRun
    {
        $headers = [];
        $imported = 0;
        $failed = 0;
        $rowErrors = [];
        $rowsRead = 0;
        $fields = $this->importableFields($resource);
        $fieldKeys = $fields->pluck('key')->all();

        $run = CoreDataTransferRun::query()->create([
            'requested_by' => $actor?->id,
            'resource_key' => $resource['key'],
            'source_module' => $resource['source_module'] ?? 'core-platform',
            'type' => 'import',
            'status' => 'processing',
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'records_total' => 0,
            'records_processed' => 0,
            'records_failed' => 0,
            'metadata' => [
                'fields' => $fieldKeys,
            ],
        ]);

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el archivo CSV para importar.');
        }

        try {
            $headers = $this->normalizeCsvRow(fgetcsv($handle) ?: []);

            if ($headers === []) {
                throw new RuntimeException('El archivo CSV no contiene encabezados.');
            }

            $missingHeaders = collect($fieldKeys)
                ->filter(fn (string $fieldKey): bool => ! in_array($fieldKey, $headers, true))
                ->values()
                ->all();

            if ($missingHeaders !== []) {
                throw new RuntimeException(sprintf(
                    'Faltan columnas requeridas para importar: %s.',
                    implode(', ', $missingHeaders),
                ));
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowsRead++;
                $normalizedRow = $this->normalizeCsvRow($row);

                if ($this->rowIsEmpty($normalizedRow)) {
                    continue;
                }

                $payload = [];

                foreach ($headers as $index => $header) {
                    if (! in_array($header, $fieldKeys, true)) {
                        continue;
                    }

                    $payload[$header] = $normalizedRow[$index] ?? null;
                }

                $payload = $this->normalizeImportedPayload($payload);
                $validation = Validator::make($payload, $this->importRules($resource));

                if ($validation->fails()) {
                    $failed++;
                    $rowErrors[] = [
                        'row' => $rowsRead + 1,
                        'errors' => $validation->errors()->all(),
                    ];

                    continue;
                }

                $modelClass = $resource['model'];
                /** @var Model $model */
                $model = new $modelClass();
                $model->fill($validation->validated());
                $model->save();
                $imported++;
            }
        } catch (\Throwable $exception) {
            fclose($handle);

            $run->fill([
                'status' => 'failed',
                'records_total' => $rowsRead,
                'records_processed' => $imported,
                'records_failed' => max($failed, $rowsRead === 0 ? 0 : $rowsRead - $imported),
                'error_summary' => $exception->getMessage(),
                'metadata' => array_merge($run->metadata ?? [], [
                    'headers' => $headers,
                    'row_errors' => $rowErrors,
                ]),
                'finished_at' => now(),
            ])->save();

            $this->auditLogger->record(
                eventKey: 'data-resource.import_failed',
                actor: $actor,
                entityType: 'data-resource',
                entityKey: $resource['key'],
                summary: sprintf('La importacion del recurso %s fallo.', $resource['name'] ?? $resource['key']),
                sourceModule: $resource['source_module'] ?? 'core-platform',
                context: [
                    'transfer_run_id' => $run->id,
                    'error' => $exception->getMessage(),
                ],
                organizationId: $this->tenantContext->organizationId($actor),
            );

            throw $exception;
        }

        fclose($handle);

        $status = $failed > 0
            ? ($imported > 0 ? 'completed_with_errors' : 'failed')
            : 'completed';

        $run->fill([
            'status' => $status,
            'records_total' => $imported + $failed,
            'records_processed' => $imported,
            'records_failed' => $failed,
            'error_summary' => $failed > 0 ? 'Algunas filas no pudieron importarse.' : null,
            'metadata' => array_merge($run->metadata ?? [], [
                'headers' => $headers,
                'row_errors' => array_slice($rowErrors, 0, 10),
            ]),
            'finished_at' => now(),
        ])->save();

        $this->auditLogger->record(
            eventKey: 'data-resource.imported',
            actor: $actor,
            entityType: 'data-resource',
            entityKey: $resource['key'],
            summary: sprintf('Se importo el recurso %s.', $resource['name'] ?? $resource['key']),
            sourceModule: $resource['source_module'] ?? 'core-platform',
            context: [
                'transfer_run_id' => $run->id,
                'records_total' => $run->records_total,
                'records_processed' => $run->records_processed,
                'records_failed' => $run->records_failed,
            ],
            organizationId: $this->tenantContext->organizationId($actor),
        );

        return $run->fresh();
    }

    public function recentRuns(string $resourceKey, int $limit = 15): Collection
    {
        return CoreDataTransferRun::query()
            ->where('resource_key', $resourceKey)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function serializeRun(CoreDataTransferRun $run): array
    {
        return [
            'id' => $run->id,
            'uuid' => $run->uuid,
            'resource_key' => $run->resource_key,
            'source_module' => $run->source_module,
            'type' => $run->type,
            'status' => $run->status,
            'file_name' => $run->file_name,
            'mime_type' => $run->mime_type,
            'records_total' => $run->records_total,
            'records_processed' => $run->records_processed,
            'records_failed' => $run->records_failed,
            'error_summary' => $run->error_summary,
            'metadata' => Arr::except($run->metadata ?? [], ['row_errors']),
            'row_errors' => array_slice($run->metadata['row_errors'] ?? [], 0, 5),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'created_at' => $run->created_at?->toIso8601String(),
        ];
    }

    protected function buildCsv(Collection $records, Collection $fields): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('No se pudo preparar el archivo CSV de exportacion.');
        }

        fputcsv($handle, $fields->pluck('key')->all());

        foreach ($records as $record) {
            $row = $fields
                ->map(fn (array $field): string => $this->normalizeCsvValue($record->getAttribute($field['key'])))
                ->all();

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    protected function exportFileName(string $resourceKey): string
    {
        return sprintf('%s-%s.csv', $resourceKey, now()->format('Ymd-His'));
    }

    protected function normalizeCsvValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) ($value ?? '');
    }

    protected function normalizeCsvRow(array $row): array
    {
        return array_map(
            fn (mixed $value): string => trim((string) $value),
            $row,
        );
    }

    protected function rowIsEmpty(array $row): bool
    {
        return collect($row)
            ->filter(fn (string $value): bool => $value !== '')
            ->isEmpty();
    }

    protected function normalizeImportedPayload(array $payload): array
    {
        return collect($payload)
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();
    }

    protected function importRules(array $resource): array
    {
        return $this->importableFields($resource)
            ->mapWithKeys(fn (array $field): array => [$field['key'] => $field['rules'] ?? []])
            ->all();
    }

    protected function exportableFields(array $resource): Collection
    {
        return collect($resource['fields'] ?? [])
            ->where('exportable', true)
            ->values();
    }

    protected function importableFields(array $resource): Collection
    {
        return collect($resource['fields'] ?? [])
            ->where('importable', true)
            ->values();
    }
}
