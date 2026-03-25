<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\DataEngine\DataResourceRegistry;
use App\Core\DataEngine\Models\CoreDataTransferRun;
use App\Core\DataEngine\Services\DataTransferManager;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Http\Controllers\Controller;
use App\Jobs\DataEngine\ProcessDataExportRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class DataResourceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DataResourceRegistry $resources,
        protected DataTransferManager $transfers,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function resources(Request $request): JsonResponse
    {
        $definitions = $this->resources->toFrontendPayload($request->user());

        return $this->successResponse(
            data: $definitions,
            message: 'Recursos del data engine listados',
            meta: [
                'total' => count($definitions),
            ],
        );
    }

    public function index(Request $request, string $resourceKey): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $query = $this->buildResourceQuery($request, $resource);
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $records = $query->paginate($perPage);

        return $this->successResponse(
            data: collect($records->items())
                ->map(fn (Model $record): array => $this->transformRecord($record, $resource))
                ->all(),
            message: 'Registros listados',
            meta: [
                'resource' => Arr::only($this->resources->serializeDefinition($resource), [
                    'key',
                    'name',
                    'default_sort',
                    'capabilities',
                ]),
                'pagination' => [
                    'total' => $records->total(),
                    'per_page' => $records->perPage(),
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                ],
            ],
        );
    }

    public function show(Request $request, string $resourceKey, string $recordId): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $record = $this->resolveRecord($resource, $recordId);

        if ($record === null) {
            return $this->recordNotFoundResponse();
        }

        return $this->successResponse(
            data: $this->transformRecord($record, $resource),
            message: 'Registro encontrado',
        );
    }

    public function export(Request $request, string $resourceKey): Response|JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $format = $this->normalizeExportFormat((string) $request->query('format', 'csv'));
        $mode = strtolower((string) $request->query('mode', 'sync'));

        if ($mode === 'async') {
            $run = $this->transfers->queueExport(
                $resource,
                $this->exportCriteriaFromRequest($request),
                $format,
                $request->user(),
            );

            ProcessDataExportRun::dispatch($run->id);
            $this->metrics->record(
                moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
                eventKey: 'data.export.queued',
                eventCategory: 'data-engine',
                actor: $request->user(),
                context: [
                    'resource_key' => $resourceKey,
                    'format' => $format,
                ],
            );

            return $this->successResponse(
                data: $this->transfers->serializeRun($run),
                message: 'Exportacion encolada correctamente',
            );
        }

        $query = $this->buildResourceQuery($request, $resource);
        $export = $this->transfers->export($resource, $query, $format, $request->user());
        $this->metrics->record(
            moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
            eventKey: 'data.export.completed',
            eventCategory: 'data-engine',
            actor: $request->user(),
            context: [
                'resource_key' => $resourceKey,
                'format' => $format,
            ],
        );

        return response($export['content'], 200, [
            'Content-Type' => $export['mime_type'],
            'Content-Disposition' => sprintf('attachment; filename="%s"', $export['file_name']),
        ]);
    }

    public function import(Request $request, string $resourceKey): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $validated = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:5120'],
        ])->validate();

        try {
            $run = $this->transfers->import($resource, $validated['file'], $request->user());
            $this->metrics->record(
                moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
                eventKey: 'data.import.completed',
                eventCategory: 'data-engine',
                actor: $request->user(),
                context: [
                    'resource_key' => $resourceKey,
                    'records_processed' => $run->records_processed,
                    'records_failed' => $run->records_failed,
                ],
            );
        } catch (\Throwable $exception) {
            return $this->errorResponse(
                message: 'No se pudo importar el archivo CSV',
                errors: [
                    'file' => [$exception->getMessage()],
                ],
            );
        }

        return $this->successResponse(
            data: $this->transfers->serializeRun($run),
            message: 'Importacion completada',
        );
    }

    public function transfers(Request $request, string $resourceKey): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $runs = $this->transfers->recentRuns($resourceKey)
            ->map(fn ($run): array => $this->transfers->serializeRun($run))
            ->all();

        return $this->successResponse(
            data: $runs,
            message: 'Historial de transferencias listado',
            meta: [
                'total' => count($runs),
            ],
        );
    }

    public function downloadTransfer(Request $request, CoreDataTransferRun $transferRun)
    {
        if ($transferRun->type !== 'export') {
            return $this->errorResponse(
                message: 'Solo las exportaciones generan archivos descargables.',
                status: 422,
            );
        }

        try {
            return $this->transfers->downloadStoredArtifact($transferRun);
        } catch (\Throwable $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: 404,
            );
        }
    }

    public function store(Request $request, string $resourceKey): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $payload = $this->validatePayload($request, $resource);
        $modelClass = $resource['model'];
        /** @var Model $record */
        $record = $modelClass::query()->create($payload);
        $this->metrics->record(
            moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
            eventKey: 'data.record.created',
            eventCategory: 'data-engine',
            actor: $request->user(),
            context: [
                'resource_key' => $resourceKey,
                'record_id' => $record->getKey(),
            ],
        );

        return $this->successResponse(
            data: $this->transformRecord($record->fresh(), $resource),
            message: 'Registro creado correctamente',
        );
    }

    public function update(Request $request, string $resourceKey, string $recordId): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $record = $this->resolveRecord($resource, $recordId);

        if ($record === null) {
            return $this->recordNotFoundResponse();
        }

        $payload = $this->validatePayload($request, $resource, true);
        $record->fill($payload)->save();
        $this->metrics->record(
            moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
            eventKey: 'data.record.updated',
            eventCategory: 'data-engine',
            actor: $request->user(),
            context: [
                'resource_key' => $resourceKey,
                'record_id' => $record->getKey(),
            ],
        );

        return $this->successResponse(
            data: $this->transformRecord($record->fresh(), $resource),
            message: 'Registro actualizado correctamente',
        );
    }

    public function destroy(Request $request, string $resourceKey, string $recordId): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        $record = $this->resolveRecord($resource, $recordId);

        if ($record === null) {
            return $this->recordNotFoundResponse();
        }

        $record->delete();
        $this->metrics->record(
            moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
            eventKey: 'data.record.deleted',
            eventCategory: 'data-engine',
            actor: $request->user(),
            context: [
                'resource_key' => $resourceKey,
                'record_id' => $record->getKey(),
            ],
        );

        return $this->successResponse(
            data: null,
            message: 'Registro eliminado correctamente',
        );
    }

    protected function resolveResource(Request $request, string $resourceKey): ?array
    {
        return $this->resources->findAvailable($resourceKey, $request->user(), false);
    }

    protected function resolveRecord(array $resource, string $recordId): ?Model
    {
        $modelClass = $resource['model'];

        return $modelClass::query()
            ->with($this->resourceRelations($resource))
            ->whereKey($recordId)
            ->first();
    }

    protected function buildResourceQuery(Request $request, array $resource): Builder
    {
        return $this->transfers->buildQueryFromCriteria($resource, $this->exportCriteriaFromRequest($request))
            ->with($this->resourceRelations($resource));
    }

    protected function validatePayload(Request $request, array $resource, bool $updating = false): array
    {
        return Validator::make($request->all(), $this->validationRules($resource, $updating))->validate();
    }

    protected function validationRules(array $resource, bool $updating = false): array
    {
        $rules = collect($resource['form_fields'])
            ->mapWithKeys(function (array $field) use ($updating): array {
                $fieldRules = $field['rules'] ?? [];

                if ($updating) {
                    $fieldRules = array_values(array_filter($fieldRules, fn (mixed $rule): bool => $rule !== 'required'));
                    array_unshift($fieldRules, 'sometimes');
                }

                return [$field['key'] => $fieldRules];
            })
            ->all();

        foreach ($resource['custom_fields'] ?? [] as $field) {
            $fieldRules = $field['rules'] ?? [];

            if ($updating) {
                $fieldRules = array_values(array_filter($fieldRules, fn (mixed $rule): bool => $rule !== 'required'));
                array_unshift($fieldRules, 'sometimes');
            }

            $rules['custom_fields.'.$field['key']] = $fieldRules;
        }

        return $rules;
    }

    protected function applySearch(Builder $query, Request $request, array $resource): void
    {
        $search = trim((string) $request->query('q', ''));
        $searchableFields = $resource['searchable_fields'] ?? [];

        if ($search === '' || $searchableFields === []) {
            return;
        }

        $query->where(function (Builder $builder) use ($searchableFields, $search): void {
            foreach ($searchableFields as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($field, 'like', '%'.$search.'%');
            }
        });
    }

    protected function applyFilters(Builder $query, Request $request, array $resource): void
    {
        $filters = $request->input('filters', []);
        $allowedFields = collect($resource['filter_fields'] ?? [])->pluck('key')->all();

        foreach ($filters as $field => $value) {
            if (! in_array($field, $allowedFields, true) || $value === null || $value === '') {
                continue;
            }

            $query->where($field, $value);
        }
    }

    protected function applySorting(Builder $query, Request $request, array $resource): void
    {
        $sortableFields = $resource['sortable_fields'] ?? [];
        $defaultSort = $resource['default_sort'] ?? ['field' => 'id', 'direction' => 'desc'];
        $sortBy = (string) $request->query('sort_by', $defaultSort['field'] ?? 'id');
        $sortDirection = strtolower((string) $request->query('sort_direction', $defaultSort['direction'] ?? 'desc'));

        if (! in_array($sortBy, $sortableFields, true)) {
            $sortBy = $defaultSort['field'] ?? 'id';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);
    }

    protected function transformRecord(Model $record, array $resource): array
    {
        $payload = [
            'id' => $record->getKey(),
        ];

        foreach ($resource['fields'] as $field) {
            $payload[$field['key']] = $this->normalizeValue($record->getAttribute($field['key']));

            if (($field['type'] ?? 'text') === 'relation' && is_array($field['relation'] ?? null)) {
                $displayKey = $field['relation']['display_key'] ?? $field['key'].'_label';
                $relationName = $field['relation']['name'] ?? null;
                $labelField = $field['relation']['label_field'] ?? 'nombre';
                $payload[$displayKey] = $relationName
                    ? data_get($record->getRelationValue($relationName), $labelField)
                    : null;
            }
        }

        $payload['custom_fields'] = collect($resource['custom_fields'] ?? [])
            ->mapWithKeys(fn (array $field): array => [
                $field['key'] => data_get($record->getAttribute('custom_fields') ?? [], $field['key']),
            ])
            ->all();

        return $payload + [
            'created_at' => $this->normalizeValue($record->getAttribute('created_at')),
            'updated_at' => $this->normalizeValue($record->getAttribute('updated_at')),
            'deleted_at' => $this->normalizeValue($record->getAttribute('deleted_at')),
        ];
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        return $value;
    }

    protected function resourceNotFoundResponse(): JsonResponse
    {
        return $this->errorResponse(
            message: 'Recurso no encontrado o no disponible',
            status: 404,
        );
    }

    protected function exportCriteriaFromRequest(Request $request): array
    {
        return [
            'q' => $request->query('q'),
            'filters' => $request->input('filters', []),
            'sort_by' => $request->query('sort_by'),
            'sort_direction' => $request->query('sort_direction'),
        ];
    }

    protected function normalizeExportFormat(string $format): string
    {
        return in_array($format, ['csv', 'excel', 'pdf'], true) ? $format : 'csv';
    }

    protected function recordNotFoundResponse(): JsonResponse
    {
        return $this->errorResponse(
            message: 'Registro no encontrado',
            status: 404,
        );
    }

    protected function resourceRelations(array $resource): array
    {
        return collect($resource['relation_fields'] ?? [])
            ->pluck('relation.name')
            ->filter(fn (mixed $relation): bool => is_string($relation) && $relation !== '')
            ->values()
            ->all();
    }
}
