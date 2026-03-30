<?php

namespace App\Core\Scaffolding\Support;

use Illuminate\Support\Str;

class ScaffoldNaming
{
    public static function module(string $value): array
    {
        $studly = Str::studly($value);
        $key = Str::kebab($studly);

        return [
            'input' => $value,
            'studly' => $studly,
            'key' => $key,
            'frontend_key' => $key,
            'label' => Str::headline($studly),
            'variable' => Str::camel($studly),
        ];
    }

    public static function resource(string $value): array
    {
        $studly = Str::studly($value);
        $key = Str::kebab($studly);

        return [
            'input' => $value,
            'studly' => $studly,
            'key' => $key,
            'label' => Str::headline($studly),
            'variable' => Str::camel($studly),
        ];
    }
}
