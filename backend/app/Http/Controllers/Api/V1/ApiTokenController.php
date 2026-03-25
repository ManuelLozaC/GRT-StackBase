<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Auth\Models\PersonalAccessToken;
use App\Core\Auth\Services\AccessTokenService;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiTokenController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AccessTokenService $tokens,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tokens = PersonalAccessToken::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return $this->successResponse(
            data: $tokens->map(fn (PersonalAccessToken $token): array => $this->transformToken($token))->all(),
            message: 'Tokens API listados',
            meta: [
                'total' => $tokens->count(),
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ])->validate();

        $ttlMinutes = isset($validated['expires_in_days'])
            ? ((int) $validated['expires_in_days']) * 24 * 60
            : null;

        $plainTextToken = $this->tokens->createForUser(
            user: $request->user(),
            name: $validated['name'],
            ttlMinutes: $ttlMinutes,
            metadata: [
                'type' => 'api-client',
            ],
        );

        $token = PersonalAccessToken::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->firstOrFail();

        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'auth.api_token.created',
            eventCategory: 'api-access',
            actor: $request->user(),
            context: [
                'token_id' => $token->id,
                'name' => $token->name,
            ],
        );

        return $this->successResponse(
            data: [
                'plain_text_token' => $plainTextToken,
                'token' => $this->transformToken($token),
            ],
            message: 'Token API creado',
        );
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $token = PersonalAccessToken::query()
            ->where('user_id', $request->user()->id)
            ->find($tokenId);

        if ($token === null) {
            return $this->errorResponse(
                message: 'Token API no encontrado',
                status: 404,
            );
        }

        $token->delete();

        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'auth.api_token.revoked',
            eventCategory: 'api-access',
            actor: $request->user(),
            context: [
                'token_id' => $tokenId,
            ],
        );

        return $this->successResponse(
            data: null,
            message: 'Token API revocado',
        );
    }

    protected function transformToken(PersonalAccessToken $token): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'type' => $token->metadata['type'] ?? 'session',
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'expires_at' => $token->expires_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
            'is_current_session' => false,
        ];
    }
}
