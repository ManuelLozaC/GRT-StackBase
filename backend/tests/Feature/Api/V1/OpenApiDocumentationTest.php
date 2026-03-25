<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_endpoint_exposes_real_api_structure(): void
    {
        $response = $this->getJson('/api/v1/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', 'StackBase API')
            ->assertJsonPath('components.securitySchemes.bearerAuth.scheme', 'bearer');

        $document = $response->json();

        $this->assertSame('Consultar api/v1/health', $document['paths']['/api/v1/health']['get']['summary']);
        $this->assertSame('X-StackBase-Signature', $document['paths']['/api/v1/webhooks/incoming/{receiver}']['post']['parameters'][1]['name']);
        $this->assertSame([['bearerAuth' => []]], $document['paths']['/api/v1/auth/me']['get']['security']);
    }
}
