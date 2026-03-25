<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_standard_api_response(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'estado',
                'datos' => [
                    'app',
                    'environment',
                    'timestamp',
                    'modules' => [
                        [
                            'key',
                            'name',
                            'version',
                        ],
                    ],
                ],
                'mensaje',
                'meta' => [
                    'api_version',
                ],
                'errores',
            ])
            ->assertJsonPath('estado', 'ok')
            ->assertJsonPath('meta.api_version', 'v1');
    }
}
