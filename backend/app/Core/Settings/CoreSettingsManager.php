<?php

namespace App\Core\Settings;

use App\Core\Settings\Models\CoreSetting;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CoreSettingsManager
{
    public function __construct(
        protected array $definitions,
    ) {
    }

    public function bootstrap(User $user): array
    {
        $global = $this->forScope('global');
        $organization = $this->forScope('organization', $user->organizacion_activa_id);
        $preferences = $this->forScope('user', null, $user->id);

        return [
            'global' => $global,
            'organization' => $organization,
            'company' => $organization,
            'user' => $preferences,
            'feature_flags' => $this->featureFlags($global),
        ];
    }

    public function forScope(string $scope, ?int $organizationId = null, ?int $userId = null): array
    {
        $definitions = collect($this->definitions[$scope] ?? [])
            ->map(fn (array $setting): array => $this->normalizeSetting($setting))
            ->values();

        $persisted = $this->persistedValues($scope, $organizationId, $userId);

        return $definitions
            ->map(function (array $setting) use ($persisted): array {
                return array_merge($setting, [
                    'value' => $persisted->has($setting['key'])
                        ? $persisted->get($setting['key'])
                        : $setting['default'],
                ]);
            })
            ->all();
    }

    public function resolveValues(string $scope, ?int $organizationId = null, ?int $userId = null): array
    {
        return collect($this->forScope($scope, $organizationId, $userId))
            ->mapWithKeys(fn (array $setting): array => [$setting['key'] => $setting['value']])
            ->all();
    }

    public function update(string $scope, array $payload, ?int $organizationId = null, ?int $userId = null): array
    {
        $settings = collect($this->forScope($scope, $organizationId, $userId));

        if ($settings->isEmpty()) {
            throw new DomainException('No existen definiciones de settings para este scope.');
        }

        $rules = [];
        $normalizedPayload = [];

        foreach ($settings as $setting) {
            if (! array_key_exists($setting['key'], $payload)) {
                continue;
            }

            $rules[$setting['key']] = $setting['rules'];
            $normalizedPayload[$setting['key']] = $payload[$setting['key']];
        }

        if ($rules === []) {
            return $this->forScope($scope, $organizationId, $userId);
        }

        $validated = Validator::make($normalizedPayload, $rules)->validate();

        if ($this->canUsePersistence()) {
            foreach ($validated as $settingKey => $value) {
                $query = CoreSetting::query()
                    ->where('scope', $scope)
                    ->where('setting_key', $settingKey);

                $organizationId === null
                    ? $query->whereNull('organizacion_id')
                    : $query->where('organizacion_id', $organizationId);

                $userId === null
                    ? $query->whereNull('user_id')
                    : $query->where('user_id', $userId);

                $current = $query->latest('id')->first();

                if ($current !== null) {
                    $current->forceFill([
                        'value_json' => ['value' => $value],
                    ])->save();

                    CoreSetting::query()
                        ->where('scope', $scope)
                        ->where('setting_key', $settingKey)
                        ->when($organizationId === null, fn ($cleanup) => $cleanup->whereNull('organizacion_id'), fn ($cleanup) => $cleanup->where('organizacion_id', $organizationId))
                        ->when($userId === null, fn ($cleanup) => $cleanup->whereNull('user_id'), fn ($cleanup) => $cleanup->where('user_id', $userId))
                        ->where('id', '!=', $current->id)
                        ->delete();

                    continue;
                }

                CoreSetting::query()->create([
                    'scope' => $scope,
                    'organizacion_id' => $organizationId,
                    'user_id' => $userId,
                    'setting_key' => $settingKey,
                    'value_json' => ['value' => $value],
                ]);
            }
        }

        return $this->forScope($scope, $organizationId, $userId);
    }

    protected function featureFlags(array $globalSettings): array
    {
        return collect($globalSettings)
            ->filter(fn (array $setting): bool => str_starts_with($setting['key'], 'feature_'))
            ->mapWithKeys(fn (array $setting): array => [$setting['key'] => (bool) $setting['value']])
            ->all();
    }

    protected function persistedValues(string $scope, ?int $organizationId = null, ?int $userId = null): Collection
    {
        if (! $this->canUsePersistence()) {
            return collect();
        }

        return CoreSetting::query()
            ->where('scope', $scope)
            ->where('organizacion_id', $organizationId)
            ->where('user_id', $userId)
            ->get()
            ->mapWithKeys(fn (CoreSetting $setting): array => [
                $setting->setting_key => $setting->value_json['value'] ?? null,
            ]);
    }

    protected function normalizeSetting(array $setting): array
    {
        $normalized = array_merge([
            'label' => $setting['key'] ?? 'setting',
            'type' => 'text',
            'default' => null,
            'help' => null,
            'options' => [],
            'hidden' => false,
        ], $setting);

        $rules = ['nullable'];

        if ($normalized['type'] === 'toggle') {
            $rules[] = 'boolean';
        } elseif ($normalized['type'] === 'json') {
            $rules[] = 'array';
        } else {
            $rules[] = 'string';
        }

        if ($normalized['type'] === 'select' && $normalized['options'] !== []) {
            $rules[] = 'in:'.collect($normalized['options'])->pluck('value')->implode(',');
        }

        $normalized['rules'] = $rules;

        return $normalized;
    }

    protected function canUsePersistence(): bool
    {
        try {
            return Schema::hasTable('core_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
