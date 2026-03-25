<?php

namespace App\Core\OpenApi;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class OpenApiDocumentBuilder
{
    public function __construct(
        protected Router $router,
    ) {
    }

    public function build(): array
    {
        $routes = collect($this->router->getRoutes())
            ->filter(fn (Route $route): bool => Str::startsWith($route->uri(), 'api/v1/'))
            ->reject(fn (Route $route): bool => $route->uri() === 'api/v1/openapi.json')
            ->values();

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('openapi.title'),
                'version' => config('openapi.version'),
                'description' => config('openapi.description'),
            ],
            'servers' => config('openapi.servers', []),
            'tags' => $this->buildTags($routes),
            'paths' => $this->buildPaths($routes),
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Token',
                        'description' => 'Token personal emitido por StackBase.',
                    ],
                ],
                'schemas' => [
                    'ApiSuccessEnvelope' => [
                        'type' => 'object',
                        'properties' => [
                            'estado' => ['type' => 'string', 'example' => 'ok'],
                            'datos' => ['nullable' => true],
                            'mensaje' => ['type' => 'string', 'nullable' => true],
                            'meta' => ['type' => 'object'],
                            'errores' => ['type' => 'array', 'items' => ['type' => 'object']],
                        ],
                    ],
                    'ApiErrorEnvelope' => [
                        'type' => 'object',
                        'properties' => [
                            'estado' => ['type' => 'string', 'example' => 'error'],
                            'datos' => ['nullable' => true],
                            'mensaje' => ['type' => 'string'],
                            'meta' => ['type' => 'object'],
                            'errores' => ['type' => 'array', 'items' => ['type' => 'object']],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildTags($routes): array
    {
        return $routes
            ->map(fn (Route $route): string => $this->inferTag($route))
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $tag): array => ['name' => $tag])
            ->all();
    }

    protected function buildPaths($routes): array
    {
        $paths = [];

        foreach ($routes as $route) {
            $path = '/'.preg_replace('/\{([^}]+)\?\}/', '{$1}', $route->uri());

            foreach (collect($route->methods())->reject(fn (string $method): bool => in_array($method, ['HEAD'], true)) as $method) {
                $lowerMethod = strtolower($method);
                $paths[$path][$lowerMethod] = [
                    'operationId' => $this->operationId($method, $route->uri()),
                    'tags' => [$this->inferTag($route)],
                    'summary' => $this->summaryFor($method, $route),
                    'parameters' => $this->parametersFor($route, $method),
                    'responses' => $this->responsesFor($route),
                    'security' => $this->securityFor($route),
                ];
            }
        }

        ksort($paths);

        return $paths;
    }

    protected function inferTag(Route $route): string
    {
        $uri = $route->uri();

        return match (true) {
            Str::contains($uri, 'auth/') => 'Auth',
            Str::contains($uri, 'modules') => 'Modules',
            Str::contains($uri, 'webhooks') => 'Webhooks',
            Str::contains($uri, 'settings') => 'Settings',
            Str::contains($uri, 'notifications') => 'Notifications',
            Str::contains($uri, 'demo/') => 'Demo',
            Str::contains($uri, 'data/') || Str::endsWith($uri, 'data/resources') => 'Data Engine',
            Str::contains($uri, 'metrics') || Str::contains($uri, 'operations') => 'Observability',
            Str::contains($uri, 'security') || Str::contains($uri, 'error-logs') => 'Security',
            Str::contains($uri, 'users') => 'Users',
            default => 'Core',
        };
    }

    protected function operationId(string $method, string $uri): string
    {
        $normalized = Str::of($uri)
            ->replace(['/', '{', '}', '-', '.'], ' ')
            ->squish()
            ->title()
            ->replace(' ', '');

        return strtolower($method).$normalized;
    }

    protected function summaryFor(string $method, Route $route): string
    {
        $verb = match (strtoupper($method)) {
            'GET' => 'Consultar',
            'POST' => 'Crear o ejecutar',
            'PATCH' => 'Actualizar',
            'PUT' => 'Actualizar',
            'DELETE' => 'Eliminar o revocar',
            default => 'Operar',
        };

        return $verb.' '.$route->uri();
    }

    protected function parametersFor(Route $route, string $method): array
    {
        $parameters = collect($route->parameterNames())
            ->map(fn (string $parameter): array => [
                'name' => $parameter,
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
            ])
            ->values()
            ->all();

        if ($route->uri() === 'api/v1/webhooks/incoming/{receiver}') {
            $parameters[] = [
                'name' => 'X-StackBase-Signature',
                'in' => 'header',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
                'description' => 'Firma HMAC SHA256 del payload bruto.',
            ];
        }

        return $parameters;
    }

    protected function responsesFor(Route $route): array
    {
        if (Str::contains($route->uri(), 'download')) {
            return [
                '200' => [
                    'description' => 'Archivo descargable o stream binario.',
                    'content' => [
                        'application/octet-stream' => [
                            'schema' => [
                                'type' => 'string',
                                'format' => 'binary',
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            '200' => [
                'description' => 'Respuesta exitosa estandar.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ApiSuccessEnvelope',
                        ],
                    ],
                ],
            ],
            '4XX' => [
                'description' => 'Respuesta de error controlado.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ApiErrorEnvelope',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function securityFor(Route $route): array
    {
        $middleware = collect($route->gatherMiddleware());

        if ($route->uri() === 'api/v1/webhooks/incoming/{receiver}') {
            return [];
        }

        if ($middleware->contains(fn (string $name): bool => Str::startsWith($name, 'auth-token'))) {
            return [
                ['bearerAuth' => []],
            ];
        }

        return [];
    }
}
