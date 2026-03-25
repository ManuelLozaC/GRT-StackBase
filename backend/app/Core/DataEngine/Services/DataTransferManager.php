<?php

namespace App\Core\DataEngine\Services;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Files\Services\StorageDiskResolver;
use App\Core\DataEngine\Models\CoreDataTransferRun;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class DataTransferManager
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected StorageDiskResolver $storageDisks,
    ) {
    }

    public function export(array $resource, Builder $query, string $format = 'csv', ?User $actor = null): array
    {
        $fields = $this->exportableFields($resource);
        $records = $query->get();
        $artifact = $this->buildExportArtifact($resource, $records, $fields, $format);

        $run = CoreDataTransferRun::query()->create([
            'requested_by' => $actor?->id,
            'resource_key' => $resource['key'],
            'source_module' => $resource['source_module'] ?? 'core-platform',
            'type' => 'export',
            'status' => 'completed',
            'file_name' => $artifact['file_name'],
            'mime_type' => $artifact['mime_type'],
            'records_total' => $records->count(),
            'records_processed' => $records->count(),
            'records_failed' => 0,
            'metadata' => [
                'fields' => $fields->pluck('key')->all(),
                'format' => $format,
                'mode' => 'sync',
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
                'format' => $format,
            ],
            organizationId: $this->tenantContext->organizationId($actor),
        );

        return [
            'run' => $run,
            'content' => $artifact['content'],
            'file_name' => $artifact['file_name'],
            'mime_type' => $artifact['mime_type'],
        ];
    }

    public function queueExport(array $resource, array $criteria, string $format = 'csv', ?User $actor = null): CoreDataTransferRun
    {
        return CoreDataTransferRun::query()->create([
            'requested_by' => $actor?->id,
            'resource_key' => $resource['key'],
            'source_module' => $resource['source_module'] ?? 'core-platform',
            'type' => 'export',
            'status' => 'queued',
            'file_name' => $this->exportFileName($resource['key'], $format),
            'mime_type' => $this->mimeTypeForFormat($format),
            'records_total' => 0,
            'records_processed' => 0,
            'records_failed' => 0,
            'metadata' => [
                'fields' => $this->exportableFields($resource)->pluck('key')->all(),
                'format' => $format,
                'mode' => 'async',
                'criteria' => $criteria,
            ],
        ]);
    }

    public function processQueuedExport(CoreDataTransferRun $run, array $resource): CoreDataTransferRun
    {
        $run->forceFill([
            'status' => 'processing',
        ])->save();

        $criteria = $run->metadata['criteria'] ?? [];
        $query = $this->buildQueryFromCriteria($resource, $criteria);
        $fields = $this->exportableFields($resource);
        $records = $query->get();
        $format = $run->metadata['format'] ?? 'csv';
        $artifact = $this->buildExportArtifact($resource, $records, $fields, $format);
        $storageDisk = $this->storageDisks->forDataExports();
        $storagePath = sprintf(
            'data-exports/%s/%s',
            $run->organizacion_id ?? 'global',
            $artifact['file_name'],
        );

        Storage::disk($storageDisk)->put($storagePath, $artifact['content']);

        $run->fill([
            'status' => 'completed',
            'file_name' => $artifact['file_name'],
            'mime_type' => $artifact['mime_type'],
            'records_total' => $records->count(),
            'records_processed' => $records->count(),
            'records_failed' => 0,
            'metadata' => array_merge($run->metadata ?? [], [
                'storage_disk' => $storageDisk,
                'storage_path' => $storagePath,
            ]),
            'finished_at' => now(),
        ])->save();

        $this->auditLogger->record(
            eventKey: 'data-resource.export_async_completed',
            actor: $run->requester,
            entityType: 'data-resource',
            entityKey: $resource['key'],
            summary: sprintf('Se completo la exportacion async del recurso %s.', $resource['name'] ?? $resource['key']),
            sourceModule: $resource['source_module'] ?? 'core-platform',
            context: [
                'transfer_run_id' => $run->id,
                'records_total' => $records->count(),
                'format' => $format,
                'storage_path' => $storagePath,
            ],
            organizationId: $run->organizacion_id,
        );

        return $run->fresh();
    }

    public function failQueuedExport(CoreDataTransferRun $run, \Throwable $exception): void
    {
        $run->fill([
            'status' => 'failed',
            'error_summary' => $exception->getMessage(),
            'finished_at' => now(),
        ])->save();

        $this->auditLogger->record(
            eventKey: 'data-resource.export_async_failed',
            actor: $run->requester,
            entityType: 'data-resource',
            entityKey: $run->resource_key,
            summary: sprintf('La exportacion async del recurso %s fallo.', $run->resource_key),
            sourceModule: $run->source_module ?? 'core-platform',
            context: [
                'transfer_run_id' => $run->id,
                'error' => $exception->getMessage(),
            ],
            organizationId: $run->organizacion_id,
        );
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
                'format' => 'csv',
                'mode' => 'sync',
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
        $metadata = $run->metadata ?? [];

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
            'format' => $metadata['format'] ?? null,
            'mode' => $metadata['mode'] ?? null,
            'download_url' => isset($metadata['storage_path'])
                ? route('api.v1.data.transfers.download', ['transferRun' => $run->uuid], absolute: false)
                : null,
            'metadata' => Arr::except($metadata, ['row_errors']),
            'row_errors' => array_slice($metadata['row_errors'] ?? [], 0, 5),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'created_at' => $run->created_at?->toIso8601String(),
        ];
    }

    public function downloadStoredArtifact(CoreDataTransferRun $run)
    {
        $storageDisk = $run->metadata['storage_disk'] ?? null;
        $storagePath = $run->metadata['storage_path'] ?? null;

        if (! is_string($storageDisk) || ! is_string($storagePath) || ! Storage::disk($storageDisk)->exists($storagePath)) {
            throw new RuntimeException('El archivo exportado ya no esta disponible para descarga.');
        }

        return Storage::disk($storageDisk)->download(
            $storagePath,
            $run->file_name,
            [
                'Content-Type' => $run->mime_type ?? 'application/octet-stream',
            ],
        );
    }

    public function buildQueryFromCriteria(array $resource, array $criteria): Builder
    {
        $modelClass = $resource['model'];
        /** @var Builder $query */
        $query = $modelClass::query();
        $search = trim((string) ($criteria['q'] ?? ''));
        $searchableFields = $resource['searchable_fields'] ?? [];

        if ($search !== '' && $searchableFields !== []) {
            $query->where(function (Builder $builder) use ($searchableFields, $search): void {
                foreach ($searchableFields as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($field, 'like', '%'.$search.'%');
                }
            });
        }

        $filters = is_array($criteria['filters'] ?? null) ? $criteria['filters'] : [];
        $allowedFields = collect($resource['filter_fields'] ?? [])->pluck('key')->all();

        foreach ($filters as $field => $value) {
            if (! in_array($field, $allowedFields, true) || $value === null || $value === '') {
                continue;
            }

            $query->where($field, $value);
        }

        $sortableFields = $resource['sortable_fields'] ?? [];
        $defaultSort = $resource['default_sort'] ?? ['field' => 'id', 'direction' => 'desc'];
        $sortBy = (string) ($criteria['sort_by'] ?? ($defaultSort['field'] ?? 'id'));
        $sortDirection = strtolower((string) ($criteria['sort_direction'] ?? ($defaultSort['direction'] ?? 'desc')));

        if (! in_array($sortBy, $sortableFields, true)) {
            $sortBy = $defaultSort['field'] ?? 'id';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        return $query->orderBy($sortBy, $sortDirection);
    }

    protected function buildExportArtifact(array $resource, Collection $records, Collection $fields, string $format): array
    {
        $normalizedFormat = $this->normalizeFormat($format);
        $fileName = $this->exportFileName($resource['key'], $normalizedFormat);

        return match ($normalizedFormat) {
            'excel' => [
                'content' => $this->buildExcelTable($resource, $records, $fields),
                'mime_type' => 'application/vnd.ms-excel; charset=UTF-8',
                'file_name' => $fileName,
            ],
            'pdf' => [
                'content' => $this->buildPdfDocument($resource, $records, $fields),
                'mime_type' => 'application/pdf',
                'file_name' => $fileName,
            ],
            default => [
                'content' => $this->buildCsv($records, $fields),
                'mime_type' => 'text/csv; charset=UTF-8',
                'file_name' => $fileName,
            ],
        };
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

    protected function buildExcelTable(array $resource, Collection $records, Collection $fields): string
    {
        $header = $fields
            ->map(fn (array $field): string => '<th>'.e($field['label'] ?? $field['key']).'</th>')
            ->implode('');
        $rows = $records
            ->map(function (Model $record) use ($fields): string {
                $cells = $fields
                    ->map(fn (array $field): string => '<td>'.e($this->normalizeCsvValue($record->getAttribute($field['key']))).'</td>')
                    ->implode('');

                return '<tr>'.$cells.'</tr>';
            })
            ->implode('');

        return <<<HTML
<html>
<head>
<meta charset="UTF-8">
</head>
<body>
<table border="1">
<caption>{$resource['name']}</caption>
<thead><tr>{$header}</tr></thead>
<tbody>{$rows}</tbody>
</table>
</body>
</html>
HTML;
    }

    protected function buildPdfDocument(array $resource, Collection $records, Collection $fields): string
    {
        $lines = collect([
            sprintf('%s - Export', $resource['name'] ?? $resource['key']),
            sprintf('Generado: %s', now()->toDateTimeString()),
            str_repeat('-', 90),
            $fields->pluck('label')->implode(' | '),
            str_repeat('-', 90),
        ])->merge(
            $records->take(35)->map(function (Model $record) use ($fields): string {
                return $fields
                    ->map(fn (array $field): string => mb_substr($this->normalizeCsvValue($record->getAttribute($field['key'])), 0, 24))
                    ->implode(' | ');
            }),
        )->all();

        $content = "BT\n/F1 10 Tf\n40 780 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -14 Td\n";
            }

            $content .= sprintf("(%s) Tj\n", $this->escapePdfText(mb_substr($line, 0, 110)));
        }

        $content .= "ET";

        return $this->wrapPdfObjects($content);
    }

    protected function wrapPdfObjects(string $content): string
    {
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj",
            sprintf("4 0 obj\n<< /Length %d >>\nstream\n%s\nendstream\nendobj", strlen($content), $content),
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefStart = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n", count($objects) + 1);
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF",
            count($objects) + 1,
            $xrefStart,
        );

        return $pdf;
    }

    protected function exportFileName(string $resourceKey, string $format = 'csv'): string
    {
        $extension = match ($this->normalizeFormat($format)) {
            'excel' => 'xls',
            'pdf' => 'pdf',
            default => 'csv',
        };

        return sprintf('%s-%s.%s', $resourceKey, now()->format('Ymd-His'), $extension);
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

    protected function mimeTypeForFormat(string $format): string
    {
        return match ($this->normalizeFormat($format)) {
            'excel' => 'application/vnd.ms-excel; charset=UTF-8',
            'pdf' => 'application/pdf',
            default => 'text/csv; charset=UTF-8',
        };
    }

    protected function normalizeFormat(string $format): string
    {
        return in_array($format, ['csv', 'excel', 'pdf'], true) ? $format : 'csv';
    }

    protected function escapePdfText(string $text): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\(', '\)'],
            $text,
        );
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
