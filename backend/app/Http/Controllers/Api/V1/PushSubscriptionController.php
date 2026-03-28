<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Notifications\Services\FirebasePushService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FirebasePushService $pushService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(
            data: [
                'configured' => $this->pushService->isConfigured(),
                'subscriptions' => $this->pushService->subscriptionsFor($user),
            ],
            message: 'Suscripciones push listadas',
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:120'],
            'browser' => ['nullable', 'string', 'max:120'],
            'endpoint' => ['nullable', 'string', 'max:1024'],
            'subscription' => ['nullable', 'array'],
        ]);

        $subscription = $this->pushService->register($user, $validated);

        return $this->successResponse(
            data: [
                'id' => $subscription->id,
                'is_active' => $subscription->is_active,
                'last_used_at' => $subscription->last_used_at?->toIso8601String(),
            ],
            message: 'Suscripcion push registrada',
        );
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $this->pushService->deactivate($user, $validated['token']);

        return $this->successResponse(
            data: [
                'token' => $validated['token'],
            ],
            message: 'Suscripcion push desactivada',
        );
    }
}
