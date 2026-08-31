<?php

namespace Nodex\Nexus\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Route;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\Actions\Api\GetAllByFilterActionMethod;
use Nodex\Nexus\Services\Actions\Api\GetAllByFilterPaginateActionMethod;
use Nodex\Nexus\Services\Actions\Api\GetAllPublishActionMethod;
use Nodex\Nexus\Services\Actions\Api\GetAllPublishPaginateActionMethod;
use Nodex\Nexus\Services\Actions\Api\GetEntityByFilterActionMethod;
use Nodex\Nexus\Services\FormBuilder;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\TableBuilder;

class NexusApiController extends Controller implements HasMiddleware
{
    protected ?DefaultModuleConfigurationDto $moduleConfig = null;

    public function __construct(
        protected ModuleManager $moduleManager, protected TableBuilder $tableBuilder,
        protected FormBuilder   $formBuilder,
    )
    {
    }

    public function checkPermission(string $action, Module $module): bool
    {
        return ModuleManager::checkPermission($action, $module);
    }

    public static function middleware()
    {
        $currentRoute = Route::currentRouteName();
        $globalMiddleware = [];
        $customMiddleware = [];
        if ($currentRoute) {
            $moduleName = Route::getCurrentRoute()?->parameter('moduleName') ?? null;
            $action = Route::getCurrentRoute()->parameter('action');
//            $service = app()->make(CrudService::class);
//            $module = $service->getModule($moduleName);
//            $customMiddleware = $module->getMiddlewares($action);
        }
        return array_merge($customMiddleware, $globalMiddleware);
    }

    public function action(FormRequest $request, Module $module, string $action, ?string $id = null)
    {
        $overrideController = ModuleManager::getModuleApiController($module);

        $checkController = ($overrideController && method_exists($overrideController, 'checkPermission'))
            ? $overrideController
            : $this;

        if (!$checkController->checkPermission($action, $module)) {
            return response('Access denied', 403);
        }

        $this->moduleConfig = DefaultModuleConfigurationDto::fromArray($module->config);

        if ($overrideController && method_exists($overrideController, $action)) {
            $callController = $overrideController;
            // Same propagation this fresh instance needs as NexusController::action() —
            // see its comment for why.
            $callController->moduleConfig = $this->moduleConfig;
        } elseif (method_exists($this, $action)) {
            $callController = $this;
        } else {
            return response("Action {$action} not implemented", 405);
        }

        if (isset($id)) {
            return $callController->$action(request: $request, module: $module, id: $id);
        } else {
            return $callController->$action(request: $request, module: $module);
        }
    }

    public function getAllPublish(FormRequest $request, Module $module, ?string $id = null)
    {
        return GetAllPublishActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function getAllPublishPaginate(FormRequest $request, Module $module, ?string $id = null)
    {
        return GetAllPublishPaginateActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function getAllByFilter(FormRequest $request, Module $module, ?string $id = null)
    {
        return GetAllByFilterActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function getAllByFilterPaginate(FormRequest $request, Module $module, ?string $id = null)
    {
        return GetAllByFilterPaginateActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function getEntityByFilter(FormRequest $request, Module $module, ?string $id = null)
    {
        return GetEntityByFilterActionMethod::handle($request, $this->moduleConfig, $id);
    }


}
