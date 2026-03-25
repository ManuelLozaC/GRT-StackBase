<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Modules\ModuleRegistry;
use App\Core\Security\SecurityLogger;
use App\Core\Webhooks\WebhookManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateModuleStatusRequest;
use DomainException;
use Illuminate\Http\JsonResponse;

class ModuleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditLogger $auditLogger,
        protected SecurityLogger $securityLogger,
        protected MetricsRecorder $metrics,
        protected WebhookManager $webhooks,
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
        try {
            $module = $modules->setEnabled(
                key: $moduleKey,
                enabled: (bool) $request->boolean('enabled'),
            );
        } catch (DomainException $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: 422,
            );
        }

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

        $this->securityLogger->log(
            eventKey: 'security.module_status_updated',
            actor: $request->user(),
            severity: 'warning',
            summary: 'Se actualizo el estado de un modulo.',
            context: [
                'module_key' => $moduleKey,
                'enabled' => (bool) $request->boolean('enabled'),
            ],
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'module.status.updated',
            eventCategory: 'modules',
            actor: $request->user(),
            context: [
                'module_key' => $moduleKey,
                'enabled' => (bool) $request->boolean('enabled'),
            ],
        );
        $this->webhooks->dispatch(
            moduleKey: 'core-platform',
            eventKey: 'module.status.updated',
            payload: [
                'module' => [
                    'key' => $module['key'],
                    'name' => $module['name'],
                    'enabled' => $module['enabled'],
                    'is_demo' => $module['is_demo'],
                ],
                'changed_by' => [
                    'id' => $request->user()?->id,
                    'email' => $request->user()?->email,
                ],
                'occurred_at' => now()->toIso8601String(),
            ],
            actor: $request->user(),
        );

        return $this->successResponse(
            data: $module,
            message: 'Estado del modulo actualizado',
        );
    }
}
