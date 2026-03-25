<?php

namespace App\Core\Modules;

use App\Core\Modules\Models\SystemModuleSetting;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ModuleSettingsManager
{
    public function __construct(
        protected ModuleRegistry $modules,
    ) {
    }

    public function forModule(string $moduleKey): array
    {
        $module = $this->modules->all()->firstWhere('key', $moduleKey);

        if ($module === null) {
            throw new DomainException('Modulo no encontrado.');
        }

        $definitions = collect($module['settings'] ?? [])
            ->map(fn (array $setting): array => $this->normalizeSetting($setting))
            ->values();
        $persisted = $this->persistedValues($moduleKey);

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

    public function get(string $moduleKey, string $settingKey, mixed $fallback = null): mixed
    {
        $settings = collect($this->forModule($moduleKey));
        $setting = $settings->firstWhere('key', $settingKey);

        if ($setting === null) {
            return $fallback;
        }

        return $setting['value'] ?? $setting['default'] ?? $fallback;
    }

    public function update(string $moduleKey, array $payload): array
    {
        $settings = collect($this->forModule($moduleKey));

        if ($settings->isEmpty()) {
            return [];
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
            return $this->forModule($moduleKey);
        }

        $validated = Validator::make($normalizedPayload, $rules)->validate();

        if (! $this->canUsePersistence()) {
            return $this->forModule($moduleKey);
        }

        foreach ($validated as $settingKey => $value) {
            SystemModuleSetting::query()->updateOrCreate(
                [
                    'module_key' => $moduleKey,
                    'setting_key' => $settingKey,
                ],
                [
                    'value_json' => $this->normalizeStoredValue($value),
                ],
            );
        }

        return $this->forModule($moduleKey);
    }

    protected function persistedValues(string $moduleKey): Collection
    {
        if (! $this->canUsePersistence()) {
            return collect();
        }

        return SystemModuleSetting::query()
            ->where('module_key', $moduleKey)
            ->get()
            ->mapWithKeys(fn (SystemModuleSetting $setting): array => [
                $setting->setting_key => $this->extractStoredValue($setting->value_json),
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
        ], $setting);

        $rules = ['nullable'];

        if ($normalized['type'] === 'number') {
            $rules[] = 'integer';
        } elseif ($normalized['type'] === 'toggle') {
            $rules[] = 'boolean';
        } else {
            $rules[] = 'string';
        }

        if ($normalized['type'] === 'select' && $normalized['options'] !== []) {
            $rules[] = 'in:'.collect($normalized['options'])->pluck('value')->implode(',');
        }

        $normalized['rules'] = $rules;

        return $normalized;
    }

    protected function normalizeStoredValue(mixed $value): array
    {
        return [
            'value' => $value,
        ];
    }

    protected function extractStoredValue(mixed $payload): mixed
    {
        if (is_array($payload) && array_key_exists('value', $payload)) {
            return $payload['value'];
        }

        return $payload;
    }

    protected function canUsePersistence(): bool
    {
        try {
            return Schema::hasTable('system_module_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
