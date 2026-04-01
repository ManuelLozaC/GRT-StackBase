<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Settings\CoreSettingsManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CoreSettingsManager $settings,
    ) {
    }

    public function bootstrap(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(
            data: $this->settings->bootstrap($user),
            message: 'Bootstrap de settings cargado',
        );
    }

    public function global(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: $this->settings->forScope('global'),
            message: 'Settings globales listados',
        );
    }

    public function updateGlobal(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: $this->settings->update('global', $request->all()),
            message: 'Settings globales actualizados',
        );
    }

    public function organization(Request $request): JsonResponse
    {
        return $this->company($request);
    }

    public function company(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(
            data: $this->settings->forScope('company', $user->activeCompanyId()),
            message: 'Settings de empresa listados',
        );
    }

    public function updateOrganization(Request $request): JsonResponse
    {
        return $this->updateCompany($request);
    }

    public function updateCompany(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(
            data: $this->settings->update('company', $request->all(), $user->activeCompanyId()),
            message: 'Settings de empresa actualizados',
        );
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(
            data: $this->settings->forScope('user', null, $user->id),
            message: 'Preferencias del usuario listadas',
        );
    }

    public function updateMe(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(
            data: $this->settings->update('user', $request->all(), null, $user->id),
            message: 'Preferencias del usuario actualizadas',
        );
    }
}
