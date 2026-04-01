<?php

namespace App\Core\DataEngine\Services;

use App\Core\Audit\Services\AuditLogger;
use App\Core\DataEngine\DataResourceRegistry;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DataSearchManager
{
    public function __construct(
        protected DataResourceRegistry $resources,
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function supportsSearch(array $resource): bool
    {
        return $this->engineFor($resource) === 'meilisearch' && $this->isConfigured();
    }

    public function engineFor(array $resource): string
    {
        return (string) Arr::get($resource, 'search.engine', config('search.default_engine', 'database'));
    }

    public function isConfigured(): bool
    {
        return filled(config('search.meilisearch.host')) && filled(config('search.meilisearch.master_key'));
    }

    public function searchIds(array $resource, string $query, ?User $actor = null, int $limit = 1000): array
    {
        if (! $this->supportsSearch($resource)) {
            return [
                'engine' => 'database',
                'fallback' => true,
                'ids' => [],
                'estimated_total_hits' => null,
            ];
        }

        $organizationId = $this->tenantContext->companyId($actor);
        $payload = [
            'q' => $query,
            'limit' => $limit,
            'attributesToRetrieve' => ['id'],
        ];

        if ($organizationId !== null) {
            $payload['filter'] = sprintf('organizacion_id = %d', $organizationId);
        }

        try {
            $response = $this->client()
                ->post(sprintf('/indexes/%s/search', $this->indexUid($resource)), $payload)
                ->throw()
                ->json();

            return [
                'engine' => 'meilisearch',
                'fallback' => false,
                'ids' => collect($response['hits'] ?? [])
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->values()
                    ->all(),
                'estimated_total_hits' => $response['estimatedTotalHits'] ?? null,
            ];
        } catch (\Throwable) {
            return [
                'engine' => 'database',
                'fallback' => true,
                'ids' => [],
                'estimated_total_hits' => null,
            ];
        }
    }

    public function syncRecord(array $resource, Model $record): void
    {
        if (! $this->supportsSearch($resource)) {
            return;
        }

        try {
            $freshRecord = $record->newQueryWithoutScopes()
                ->with($this->resourceRelations($resource))
                ->whereKey($record->getKey())
                ->first();

            if (! $freshRecord) {
                return;
            }

            $this->ensureIndex($resource);
            $this->client()
                ->post(sprintf('/indexes/%s/documents', $this->indexUid($resource)), [$this->documentFor($resource, $freshRecord)])
                ->throw();
        } catch (\Throwable) {
            // La indexacion incremental nunca debe romper el flujo CRUD principal.
        }
    }

    public function deleteRecord(array $resource, int|string $recordId): void
    {
        if (! $this->supportsSearch($resource)) {
            return;
        }

        try {
            $this->client()
                ->delete(sprintf('/indexes/%s/documents/%s', $this->indexUid($resource), $recordId))
                ->throw();
        } catch (\Throwable) {
            // La eliminacion incremental nunca debe romper el flujo CRUD principal.
        }
    }

    public function reindex(array $resource, ?User $actor = null): array
    {
        if (! $this->supportsSearch($resource)) {
            throw new RuntimeException('La busqueda real no esta habilitada para este recurso.');
        }

        $this->ensureIndex($resource);

        $query = $resource['model']::query()->withoutGlobalScopes()->with($this->resourceRelations($resource));
        $documents = $query->get()->map(fn (Model $record): array => $this->documentFor($resource, $record))->values()->all();

        $this->client()
            ->put(sprintf('/indexes/%s/documents', $this->indexUid($resource)), $documents)
            ->throw();

        $stats = $this->stats($resource);
        $snapshot = [
            'reindexed_at' => now()->toIso8601String(),
            'documents_indexed' => count($documents),
            'index_uid' => $this->indexUid($resource),
            'stats' => $stats,
        ];

        Cache::put($this->cacheKey($resource), $snapshot, now()->addDays(30));

        $this->metrics->record(
            moduleKey: (string) ($resource['source_module'] ?? 'core-platform'),
            eventKey: 'data.search.reindexed',
            eventCategory: 'data-engine',
            actor: $actor,
            context: [
                'resource_key' => $resource['key'],
                'engine' => 'meilisearch',
                'documents_indexed' => count($documents),
            ],
        );

        $this->auditLogger->record(
            eventKey: 'data-resource.search_reindexed',
            actor: $actor,
            entityType: 'data-resource',
            entityKey: $resource['key'],
            summary: sprintf('Se reindexo la busqueda del recurso %s.', $resource['name'] ?? $resource['key']),
            sourceModule: $resource['source_module'] ?? 'core-platform',
            context: [
                'engine' => 'meilisearch',
                'documents_indexed' => count($documents),
                'index_uid' => $this->indexUid($resource),
            ],
            organizationId: $this->tenantContext->companyId($actor),
        );

        return $snapshot;
    }

    public function status(array $resource): array
    {
        $cached = Cache::get($this->cacheKey($resource), []);
        $engine = $this->supportsSearch($resource) ? 'meilisearch' : 'database';

        return [
            'engine' => $engine,
            'configured' => $this->supportsSearch($resource),
            'index_uid' => $this->indexUid($resource),
            'stats' => $this->supportsSearch($resource) ? $this->stats($resource) : null,
            'last_reindex' => $cached,
        ];
    }

    protected function stats(array $resource): ?array
    {
        if (! $this->supportsSearch($resource)) {
            return null;
        }

        try {
            $response = $this->client()
                ->get(sprintf('/indexes/%s/stats', $this->indexUid($resource)))
                ->throw()
                ->json();

            return [
                'number_of_documents' => $response['numberOfDocuments'] ?? 0,
                'is_indexing' => $response['isIndexing'] ?? false,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    protected function ensureIndex(array $resource): void
    {
        $uid = $this->indexUid($resource);

        try {
            $this->client()->post('/indexes', [
                'uid' => $uid,
                'primaryKey' => 'id',
            ]);
        } catch (\Throwable) {
            // Si el indice ya existe, seguimos con la configuracion posterior.
        }

        $filterable = ['organizacion_id'];

        $this->client()->put(sprintf('/indexes/%s/settings/filterable-attributes', $uid), $filterable)->throw();
        $this->client()->put(sprintf('/indexes/%s/settings/searchable-attributes', $uid), ['search_blob'])->throw();
    }

    protected function documentFor(array $resource, Model $record): array
    {
        $searchParts = collect();

        foreach ($resource['fields'] as $field) {
            if (! ($field['searchable'] ?? false)) {
                continue;
            }

            $relation = $field['relation'] ?? null;

            if (is_array($relation) && ! empty($relation['name'])) {
                $searchParts->push((string) data_get($record->getRelationValue($relation['name']), $relation['label_field'] ?? 'nombre', ''));
                continue;
            }

            $searchParts->push((string) data_get($record, $field['key'], ''));
        }

        foreach ($resource['searchable_custom_fields'] ?? [] as $field) {
            $searchParts->push((string) data_get($record->getAttribute('custom_fields') ?? [], $field['key'], ''));
        }

        return [
            'id' => (int) $record->getKey(),
            'organizacion_id' => (int) ($record->getAttribute('organizacion_id') ?? 0),
            'search_blob' => $searchParts
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->implode(' '),
        ];
    }

    protected function resourceRelations(array $resource): array
    {
        return collect($resource['relation_fields'] ?? [])
            ->pluck('relation.name')
            ->filter(fn (mixed $relation): bool => is_string($relation) && $relation !== '')
            ->values()
            ->all();
    }

    protected function indexUid(array $resource): string
    {
        return (string) Arr::get($resource, 'search.index_uid', config('search.meilisearch.index_prefix', 'grt_stackbase_').$resource['key']);
    }

    protected function cacheKey(array $resource): string
    {
        return 'data_engine_search_last_reindex:'.$resource['key'];
    }

    protected function client()
    {
        return Http::baseUrl((string) config('search.meilisearch.host'))
            ->withHeaders([
                'Authorization' => 'Bearer '.config('search.meilisearch.master_key'),
            ])
            ->acceptJson()
            ->timeout((int) config('search.meilisearch.timeout_seconds', 5));
    }
}
