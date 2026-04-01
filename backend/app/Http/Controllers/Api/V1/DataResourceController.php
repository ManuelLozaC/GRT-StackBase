<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\DataEngine\DataResourceRegistry;
use App\Core\DataEngine\Models\CoreDataTransferRun;
use App\Core\DataEngine\Services\DataSearchManager;
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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class DataResourceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DataResourceRegistry $resources,
        protected DataTransferManager $transfers,
        protected DataSearchManager $search,
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

        $searchQuery = trim((string) $request->query('q', ''));
        $searchResult = null;

        if ($searchQuery !== '') {
            $searchResult = $this->search->searchIds($resource, $searchQuery, $request->user());
        }

        $query = $this->buildResourceQuery($request, $resource, $searchResult['ids'] ?? null, ($searchResult['fallback'] ?? true) === false);
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $records = $query->paginate($perPage);

        return $this->successResponse(
            data: collect($records->items())
                ->map(fn (Model $record): array => $this->transformRecord($record, $resource))
                ->all(),
            message: 'Registros listados',
            meta: [
                'resource' => Arr::only($this->resources->serializeDefinition($resource, $request->user()), [
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
                'search' => [
                    'engine' => $searchResult['engine'] ?? 'database',
                    'fallback' => $searchResult['fallback'] ?? false,
                    'estimated_total_hits' => $searchResult['estimated_total_hits'] ?? null,
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

        if (! $this->resources->userCanPerform($resource, $request->user(), 'export')) {
            return $this->forbiddenActionResponse('exportar este recurso');
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

        if (! $this->resources->userCanPerform($resource, $request->user(), 'import')) {
            return $this->forbiddenActionResponse('importar sobre este recurso');
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

        if (! $this->resources->userCanPerform($resource, $request->user(), 'create')) {
            return $this->forbiddenActionResponse('crear registros en este recurso');
        }

        $payload = $this->validatePayload($request, $resource);
        $modelClass = $resource['model'];
        /** @var Model $record */
        $record = $modelClass::query()->create($payload);
        $this->search->syncRecord($resource, $record);
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

        if (! $this->resources->userCanPerform($resource, $request->user(), 'update')) {
            return $this->forbiddenActionResponse('actualizar este recurso');
        }

        $payload = $this->validatePayload($request, $resource, true);
        $record->fill($payload)->save();
        $this->search->syncRecord($resource, $record);
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

        if (! $this->resources->userCanPerform($resource, $request->user(), 'delete')) {
            return $this->forbiddenActionResponse('eliminar registros de este recurso');
        }

        $recordPrimaryKey = $record->getKey();
        $record->delete();
        $this->search->deleteRecord($resource, $recordPrimaryKey);
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

    public function duplicate(Request $request, string $resourceKey, string $recordId): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        if (! (($resource['capabilities']['duplicate'] ?? false) || ($resource['record_actions']['duplicate'] ?? false))) {
            return $this->errorResponse(
                message: 'Este recurso no permite duplicar registros.',
                status: 422,
            );
        }

        if (! $this->resources->userCanPerform($resource, $request->user(), 'duplicate')) {
            return $this->forbiddenActionResponse('duplicar registros de este recurso');
        }

        $record = $this->resolveRecord($resource, $recordId);

        if ($record === null) {
            return $this->recordNotFoundResponse();
        }

        $duplicate = $this->buildDuplicateRecord($record, $resource);
        $duplicate->save();
        $this->search->syncRecord($resource, $duplicate);
        $this->metrics->record(
            moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
            eventKey: 'data.record.duplicated',
            eventCategory: 'data-engine',
            actor: $request->user(),
            context: [
                'resource_key' => $resourceKey,
                'record_id' => $record->getKey(),
                'duplicate_id' => $duplicate->getKey(),
            ],
        );

        return $this->successResponse(
            data: $this->transformRecord($duplicate->fresh($this->resourceRelations($resource)), $resource),
            message: 'Registro duplicado correctamente',
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

    protected function buildResourceQuery(Request $request, array $resource, ?array $searchIds = null, bool $preserveSearchOrder = false): Builder
    {
        return $this->transfers->buildQueryFromCriteria($resource, $this->exportCriteriaFromRequest($request, $searchIds, $preserveSearchOrder))
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

    public function searchStatus(Request $request, string $resourceKey): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        return $this->successResponse(
            data: $this->search->status($resource),
            message: 'Estado de busqueda listado',
        );
    }

    public function reindexSearch(Request $request, string $resourceKey): JsonResponse
    {
        $resource = $this->resolveResource($request, $resourceKey);

        if ($resource === null) {
            return $this->resourceNotFoundResponse();
        }

        if (! $this->resources->userHasPermission($request->user(), 'data-engine.search.manage')) {
            return $this->forbiddenActionResponse('reindexar la busqueda de este recurso');
        }

        $result = $this->search->reindex($resource, $request->user());

        return $this->successResponse(
            data: $result,
            message: 'Reindexacion completada correctamente',
        );
    }

    protected function exportCriteriaFromRequest(Request $request, ?array $searchIds = null, bool $preserveSearchOrder = false): array
    {
        return [
            'q' => $request->query('q'),
            'filters' => $request->input('filters', []),
            'sort_by' => $request->query('sort_by'),
            'sort_direction' => $request->query('sort_direction'),
            'search_ids' => $searchIds,
            'preserve_search_order' => $preserveSearchOrder,
        ];
    }

    protected function buildDuplicateRecord(Model $record, array $resource): Model
    {
        /** @var Model $duplicate */
        $duplicate = $record->replicate(['uuid', 'created_at', 'updated_at', 'deleted_at']);
        $modelClass = $resource['model'];

        foreach ($resource['fields'] as $field) {
            $key = $field['key'];

            if (! in_array($key, $duplicate->getFillable(), true)) {
                continue;
            }

            if ($key === 'slug') {
                $duplicate->setAttribute($key, $this->uniqueDuplicateSlug($modelClass, (string) $record->getAttribute($key)));
                continue;
            }

            if (in_array($key, ['nombre', 'name'], true)) {
                $duplicate->setAttribute($key, $this->duplicateLabel((string) $record->getAttribute($key)));
                continue;
            }
        }

        if (in_array('uuid', $duplicate->getFillable(), true)) {
            $duplicate->setAttribute('uuid', (string) Str::uuid());
        }

        return $duplicate;
    }

    protected function duplicateLabel(string $value): string
    {
        $normalized = trim($value);

        return $normalized === '' ? 'Registro copia' : $normalized.' (copia)';
    }

    protected function uniqueDuplicateSlug(string $modelClass, string $baseSlug): string
    {
        $seed = trim($baseSlug) !== '' ? $baseSlug : 'registro';
        $candidate = Str::slug($seed).'-copia';
        $attempt = 2;

        while ($modelClass::query()->where('slug', $candidate)->exists()) {
            $candidate = Str::slug($seed).'-copia-'.$attempt;
            $attempt++;
        }

        return $candidate;
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

    protected function forbiddenActionResponse(string $actionDescription): JsonResponse
    {
        return $this->errorResponse(
            message: "No tienes permisos para {$actionDescription}.",
            status: 403,
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
