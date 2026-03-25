<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\DataEngine\DataResourceRegistry;
use App\Core\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class DataResourceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DataResourceRegistry $resources,
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

        $modelClass = $resource['model'];
        /** @var Builder $query */
        $query = $modelClass::query();
        $this->applySearch($query, $request, $resource);
        $this->applyFilters($query, $request, $resource);
        $this->applySorting($query, $request, $resource);

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

        return $this->successResponse(
            data: null,
            message: 'Registro eliminado correctamente',
        );
    }

    protected function resolveResource(Request $request, string $resourceKey): ?array
    {
        return $this->resources->findAvailable($resourceKey, $request->user());
    }

    protected function resolveRecord(array $resource, string $recordId): ?Model
    {
        $modelClass = $resource['model'];

        return $modelClass::query()->whereKey($recordId)->first();
    }

    protected function validatePayload(Request $request, array $resource, bool $updating = false): array
    {
        $rules = [];

        foreach ($resource['form_fields'] as $field) {
            $fieldRules = $field['rules'] ?? [];

            if ($updating) {
                $fieldRules = array_values(array_filter($fieldRules, fn (mixed $rule): bool => $rule !== 'required'));
                array_unshift($fieldRules, 'sometimes');
            }

            $rules[$field['key']] = $fieldRules;
        }

        return Validator::make($request->all(), $rules)->validate();
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
        }

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

    protected function recordNotFoundResponse(): JsonResponse
    {
        return $this->errorResponse(
            message: 'Registro no encontrado',
            status: 404,
        );
    }
}
