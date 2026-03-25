<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Modules\ModuleSettingsManager;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleSettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ModuleSettingsManager $settings,
    ) {
    }

    public function show(string $moduleKey): JsonResponse
    {
        try {
            $settings = $this->settings->forModule($moduleKey);
        } catch (DomainException $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: 404,
            );
        }

        return $this->successResponse(
            data: $settings,
            message: 'Settings del modulo listados',
            meta: [
                'total' => count($settings),
            ],
        );
    }

    public function update(Request $request, string $moduleKey): JsonResponse
    {
        try {
            $settings = $this->settings->update(
                $moduleKey,
                $request->input('settings', []),
            );
        } catch (DomainException $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: 404,
            );
        }

        return $this->successResponse(
            data: $settings,
            message: 'Settings del modulo actualizados',
            meta: [
                'total' => count($settings),
            ],
        );
    }
}
