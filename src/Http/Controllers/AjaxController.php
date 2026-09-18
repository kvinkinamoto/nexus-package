<?php

namespace Nodex\Nexus\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\RelationService;

class AjaxController extends Controller
{
    public function __construct(protected RelationService $relationService) {}

    public function relationSearch(Request $request, Module $module, string $relation)
    {
        if (! ModuleManager::checkPermission('index', $module)) {
            return response()->json(['results' => []], 403);
        }

        $config = DefaultModuleConfigurationDto::fromArray($module->config);
        $relationConfig = $config->relations->is_available[$relation] ?? null;

        if (! $relationConfig) {
            return response()->json(['results' => []]);
        }

        $instance = new $config->model;
        if (! method_exists($instance, $relation)) {
            return response()->json(['results' => []]);
        }

        $relatedModel = $instance->$relation()->getRelated();
        $results = $this->relationService->search($relatedModel, $relationConfig, $request->get('q'));

        return response()->json([
            'results' => $results,
        ]);
    }
}
