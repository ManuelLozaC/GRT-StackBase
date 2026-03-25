<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Modules\ModuleRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateModuleStatusRequest;
use Illuminate\Http\JsonResponse;

class ModuleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditLogger $auditLogger,
    ) {
    }

    public function index(ModuleRegistry $modules): JsonResponse
    {
        return $this->successResponse(
            data: $modules->all()->all(),
            message: 'Modulos disponibles',
            meta: [
                'total' => $modules->all()->count(),
            ],
        );
    }

    public function updateStatus(
        UpdateModuleStatusRequest $request,
        ModuleRegistry $modules,
        string $moduleKey,
    ): JsonResponse {
        $module = $modules->setEnabled(
            key: $moduleKey,
            enabled: (bool) $request->boolean('enabled'),
        );

        if ($module === null) {
            return $this->errorResponse(
                message: 'Modulo no encontrado',
                status: 404,
            );
        }

        $this->auditLogger->record(
            eventKey: 'module.status.updated',
            actor: $request->user(),
            entityType: 'system_module',
            entityKey: $moduleKey,
            summary: 'Se actualizo el estado de un modulo',
            sourceModule: 'core-platform',
            context: [
                'enabled' => (bool) $request->boolean('enabled'),
            ],
        );

        return $this->successResponse(
            data: $module,
            message: 'Estado del modulo actualizado',
        );
    }
}
