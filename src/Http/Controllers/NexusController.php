<?php

namespace Nodex\Nexus\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Dto\Widgets\WidgetContext;
use Nodex\Nexus\Enums\WidgetSurface;
use Nodex\Nexus\Events\ModuleActionExecuted;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\Actions\Admin\BoolToggleActionMethod;
use Nodex\Nexus\Services\Actions\Admin\CallGroupActionMethod;
use Nodex\Nexus\Services\Actions\Admin\DeleteActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\DeleteActionMethod;
use Nodex\Nexus\Services\Actions\Admin\DeletePermanentActionMethod;
use Nodex\Nexus\Services\Actions\Admin\DuplicateActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\DuplicateActionMethod;
use Nodex\Nexus\Services\Actions\Admin\ImpersonateActionMethod;
use Nodex\Nexus\Services\Actions\Admin\ImportActionMethod;
use Nodex\Nexus\Services\Actions\Admin\OrderingActionMethod;
use Nodex\Nexus\Services\Actions\Admin\PublishActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\RestoreActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\RestoreActionMethod;
use Nodex\Nexus\Services\Actions\Admin\StoreActionMethod;
use Nodex\Nexus\Services\Actions\Admin\UnpublishActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\UpdateActionMethod;
use Nodex\Nexus\Services\Actions\Admin\ViewActionMethod;
use Nodex\Nexus\Services\FormBuilder;
use Nodex\Nexus\Services\GlobalSearchService;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\RelatedEntityFieldService;
use Nodex\Nexus\Services\SchemaColumnsCache;
use Nodex\Nexus\Services\TableBuilder;
use Nodex\Nexus\Services\Widgets\AdminDashboardRenderer;
use Nodex\Nexus\Services\Widgets\DashboardLayoutResolver;
use Nodex\Nexus\Services\Widgets\WidgetPermissionChecker;
use Nodex\Nexus\Services\Widgets\WidgetRegistry;

class NexusController extends Controller implements HasMiddleware
{
    protected ?DefaultModuleConfigurationDto $moduleConfig = null;

    public function __construct(
        protected ModuleManager $moduleManager,
        protected TableBuilder $tableBuilder,
        protected FormBuilder $formBuilder,
        protected RelatedEntityFieldService $relatedEntityFieldService,
    ) {}

    public function admin(
        DashboardLayoutResolver $layoutResolver,
        AdminDashboardRenderer $dashboardRenderer,
        WidgetRegistry $widgetRegistry,
    ) {
        $user = auth()->user();
        $widgetKeys = $layoutResolver->resolveFor($user);

        $context = new WidgetContext(
            surface: WidgetSurface::Admin,
            user: $user,
            locale: app()->getLocale(),
        );

        $cards = $dashboardRenderer->render($widgetKeys, $context);
        $availableWidgets = array_filter(
            $widgetRegistry->forSurface(WidgetSurface::Admin),
            fn (array $entry) => WidgetPermissionChecker::check($entry['meta'], $user),
        );

        return view('nexus::'.config('nexus.template').'.pages.dashboard', [
            'cards' => $cards,
            'widgetKeys' => $widgetKeys,
            'availableWidgets' => $availableWidgets,
        ]);
    }

    /**
     * Fetched by the inline script in pages/dashboard.blade.php for every
     * placeholder AdminDashboardRenderer emitted for a #[Widget(lazy: true)]
     * card. Reuses renderCard() rather than render() so a stale/uninstalled
     * key or a since-revoked permission degrades to an empty card instead of
     * a 500 or leaking a widget the requester can no longer see.
     */
    public function widgetCard(Request $request, string $key, AdminDashboardRenderer $dashboardRenderer)
    {
        $context = new WidgetContext(
            surface: WidgetSurface::Admin,
            user: auth()->user(),
            locale: app()->getLocale(),
        );

        return response()->json(['html' => $dashboardRenderer->renderCard($key, $context) ?? '']);
    }

    /**
     * Backs the header search box in both themes — see
     * GlobalSearchService's docblock for why this only searches
     * #[Column(searchable:)] columns rather than every physical column.
     */
    public function globalSearch(Request $request, GlobalSearchService $searchService)
    {
        return response()->json([
            'results' => $searchService->search((string) $request->query('q', '')),
        ]);
    }

    /**
     * Backs the header notification bell in both themes. Response shape
     * ({data, meta:{last_page}}) matches exactly what layouts/header.blade.php's
     * JS already expects — see that file's docblock for why.
     */
    public function notifications(Request $request)
    {
        $paginator = auth()->user()->notifications()->latest()->paginate(10);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => ['last_page' => $paginator->lastPage()],
        ]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['status' => 'success']);
    }

    public function markNotificationRead(Request $request, string $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();

        $notification?->markAsRead();

        return response()->json(['success' => true]);
    }

    public function saveDashboardLayout(Request $request, DashboardLayoutResolver $layoutResolver)
    {
        $request->validate([
            'widgets' => 'required|array',
            'widgets.*' => 'string',
        ]);

        $layoutResolver->saveFor(auth()->user(), $request->widgets);

        return response()->json(['success' => true]);
    }

    public function checkPermission(string $action, Module $module): bool
    {
        return ModuleManager::checkPermission($action, $module);
    }

    public function asyncRelation(Request $request, Module $module, string $relation)
    {
        if (! ModuleManager::checkPermission('index', $module)) {
            return response()->json(['success' => false, 'error' => __('nexus::translate.alert.access_denied')], 403);
        }

        $config = DefaultModuleConfigurationDto::fromArray($module->config);
        $modelClass = $config->model;
        $instance = new $modelClass;

        if (! method_exists($instance, $relation)) {
            return response()->json(['success' => false, 'error' => "Relation {$relation} not found"]);
        }

        $relationObj = $instance->$relation();
        if (! $relationObj instanceof Relation) {
            return response()->json(['success' => false, 'error' => "Method {$relation} is not a relation"]);
        }

        $relatedModel = $relationObj->getRelated();
        $query = $relatedModel->query();

        // Determine PK and showField
        $primaryKey = $relatedModel->getKeyName();
        $tableName = $relatedModel->getTable();
        $columns = SchemaColumnsCache::get($tableName);

        // Priority 1: Specifically requested column (e.g. from category.name)
        // Priority 2: Configured showField
        // Priority 3: Common defaults (name, title, etc)
        $requestedCol = $request->get('column');
        if ($requestedCol && str_contains($requestedCol, '.')) {
            $requestedCol = explode('.', $requestedCol)[1];
        }

        $showField = $requestedCol;
        if (! $showField || ! in_array($showField, $columns)) {
            $showField = $config->relations->is_available[$relation]?->showField ?? 'name';
        }

        if (! in_array($showField, $columns)) {
            $showField = in_array('title', $columns) ? 'title' : (in_array('name', $columns) ? 'name' : $primaryKey);
        }

        // Search logic
        if ($q = $request->get('q')) {
            $query->where(function ($sub) use ($showField, $primaryKey, $columns, $q) {
                $sub->where($showField, 'LIKE', "%{$q}%");

                // Also search in name/title if they weren't the primary showField
                foreach (['name', 'title', 'first_name', 'last_name', 'email'] as $extra) {
                    if (in_array($extra, $columns) && $extra !== $showField) {
                        $sub->orWhere($extra, 'LIKE', "%{$q}%");
                    }
                }

                if (is_numeric($q)) {
                    $sub->orWhere($primaryKey, $q);
                }
            });
        }

        $results = $query->select($primaryKey, $showField)
            ->limit(20)
            ->get()
            ->map(function ($item) use ($showField, $primaryKey) {
                $label = $item->$showField;

                // Handle Spatie Translatable if the field is translatable
                if (method_exists($item, 'isTranslatableAttribute') && $item->isTranslatableAttribute($showField)) {
                    if (empty($label)) {
                        $translations = method_exists($item, 'getTranslations') ? $item->getTranslations($showField) : [];
                        if (! empty($translations)) {
                            $label = $translations[app()->getLocale()]
                                ?? $translations[config('app.fallback_locale')]
                                ?? reset($translations);
                        }
                    }
                }

                return [
                    'id' => $item->$primaryKey,
                    'label' => (string) ($label ?? ''),
                ];
            });

        return response()->json([
            'success' => true,
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Actions that only render a view/form — never a state change — so they
     * are excluded from the generic ModuleActionExecuted audit event.
     */
    private const READ_ONLY_ACTIONS = ['index', 'edit', 'create', 'view'];

    public function action(FormRequest $request, Module $module, string $action, ?string $id = null)
    {
        $overrideController = ModuleManager::getModuleAdminController($module);

        $this->moduleConfig = DefaultModuleConfigurationDto::fromArray($module->config);

        $checkController = ($overrideController && method_exists($overrideController, 'checkPermission'))
            ? $overrideController
            : $this;

        if (! $checkController->checkPermission($action, $module)) {
            return redirect()->route('nexus.admin')
                ->with('warning', 'Access denied')
                ->with('alert_message', __('nexus::translate.alert.access_denied'))
                ->with('alert_type', 'warning');
        }

        if ($overrideController && method_exists($overrideController, $action)) {
            $callController = $overrideController;
            // $overrideController is a fresh app()->make() instance (see
            // ModuleManager::getController()), separate from $this — without this,
            // any action it inherits from NexusController rather than overriding
            // itself (create/store/edit/update/...) would read a null moduleConfig.
            $callController->moduleConfig = $this->moduleConfig;
        } elseif (method_exists($this, $action)) {
            $callController = $this;
        } else {
            // Check if this action is a group action defined in the module config
            $actionGroup = $this->moduleConfig->table->actionGroup ?? [];
            if (isset($actionGroup[$action])) {
                $response = CallGroupActionMethod::handle($request, $this->moduleConfig, $actionGroup[$action]);
                event(new ModuleActionExecuted($module->name, $action, ids: $request->input('items')));

                return $response;
            }

            return response("Action {$action} not implemented", 405);
        }

        $response = isset($id)
            ? $callController->$action(request: $request, module: $module, id: $id)
            : $callController->$action(request: $request, module: $module);

        if (! in_array($action, self::READ_ONLY_ACTIONS, true)) {
            event(new ModuleActionExecuted($module->name, $action, id: $id, ids: $request->input('items')));
        }

        if (method_exists($response, 'with') && session()->has('alert_message')) {
            return $response->with('alert_message', __('nexus::translate.alert.update_success'))
                ->with('alert_type', 'success');
        }

        return $response;
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

    public function index(FormRequest $request, Module $module, ?string $id = null)
    {
        $moduleRequest = GetModuleRequestAction::getRequestByMethodName('index', $this->moduleConfig);
        $moduleRequest?->validated();

        $module = $this->moduleManager->getInstalledModule($module->name);

        return view('nexus::'.config('nexus.template').'.pages.indexLivewire', [
            'module' => $module,
        ]);
    }

    public function edit(FormRequest $request, Module $module, ?string $id = null)
    {
        return view('nexus::'.config('nexus.template').'.pages.editLivewire', [
            'module' => $module,
            'id' => $id,
        ]);
    }

    public function view(FormRequest $request, Module $module, ?string $id = null)
    {
        return ViewActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function update(FormRequest $request, Module $module, ?string $id = null)
    {
        return UpdateActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function create(FormRequest $request, Module $module, ?string $id = null)
    {
        return view('nexus::'.config('nexus.template').'.pages.createLivewire', [
            'module' => $module,
        ]);
    }

    public function store(FormRequest $request, Module $module, ?string $id = null)
    {
        return StoreActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function delete(FormRequest $request, Module $module, ?string $id = null)
    {
        return DeleteActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function restore(FormRequest $request, Module $module, ?string $id = null)
    {
        return RestoreActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function deletePermanent(FormRequest $request, Module $module, ?string $id = null)
    {
        return DeletePermanentActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function deleteGroup(FormRequest $request, Module $module)
    {
        return DeleteActionGroupMethod::handle($request, $this->moduleConfig);
    }

    public function restoreGroup(FormRequest $request, Module $module)
    {
        return RestoreActionGroupMethod::handle($request, $this->moduleConfig);
    }

    public function publishGroup(FormRequest $request, Module $module)
    {
        return PublishActionGroupMethod::handle($request, $this->moduleConfig);
    }

    public function unpublishGroup(FormRequest $request, Module $module)
    {
        return UnpublishActionGroupMethod::handle($request, $this->moduleConfig);
    }

    public function boolToggle(FormRequest $request, Module $module, ?string $id = null)
    {
        return BoolToggleActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function ordering(FormRequest $request, Module $module, ?string $id = null)
    {
        return OrderingActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function duplicate(FormRequest $request, Module $module, ?string $id = null)
    {
        return DuplicateActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function impersonate(FormRequest $request, Module $module, ?string $id = null)
    {
        return ImpersonateActionMethod::handle($request, $this->moduleConfig, $id);
    }

    public function stopImpersonating(Request $request)
    {
        $impersonatorId = $request->session()->get('nexus_impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('nexus.admin');
        }

        $impersonated = auth()->user();

        $request->session()->forget('nexus_impersonator_id');

        activity('impersonation')
            ->causedBy(User::find($impersonatorId))
            ->performedOn($impersonated)
            ->log('impersonation_stopped');

        Auth::guard('web')->loginUsingId($impersonatorId);

        return redirect()->route('nexus.module.action', ['module' => 'user', 'action' => 'index']);
    }

    public function duplicateGroup(FormRequest $request, Module $module)
    {
        return DuplicateActionGroupMethod::handle($request, $this->moduleConfig);
    }

    public function saveTableColumns(Request $request, string $module)
    {
        $request->validate([
            'columns' => 'required|array',
            'columns.*' => 'string',
        ]);

        DB::table('nexus_user_table_preferences')
            ->updateOrInsert(
                ['user_id' => auth()->id(), 'module' => $module],
                ['visible_columns' => json_encode($request->columns), 'updated_at' => now()]
            );

        return response()->json(['success' => true]);
    }

    public function saveDynamicFilters(Request $request, string $module)
    {
        $request->validate([
            'filters' => 'nullable|array',
            'filters.*.column' => 'required|string',
            'filters.*.operator' => 'required|string',
            'filters.*.value' => 'nullable',
            'filters.*.logic' => 'nullable|string',
        ]);

        DB::table('nexus_user_table_preferences')
            ->updateOrInsert(
                ['user_id' => auth()->id(), 'module' => $module],
                [
                    'filters' => json_encode($request->filters),
                    'updated_at' => now(),
                ]
            );

        return response()->json(['success' => true]);
    }

    public function removeDynamicFilters(Request $request, string $module)
    {
        DB::table('nexus_user_table_preferences')
            ->where('user_id', auth()->id())
            ->where('module', $module)
            ->update([
                'filters' => null,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    public function import(FormRequest $request, Module $module)
    {
        return ImportActionMethod::handle($request, $this->moduleConfig);
    }

    public function export(FormRequest $request, Module $module, ?string $id = null)
    {
        $cacheKey = 'export_'.Str::random(10);

        $jobClass = ModuleManager::nexus_module_class('Export', 'Jobs\\MasterExportJob');

        dispatch(new $jobClass(
            $module->name,
            $cacheKey,
            $request->all(),
            auth()->id(),
            $request->chunkSize
        ));

        return response()->json([
            'cacheKey' => $cacheKey,
        ]);
    }

    public function progressExport(Request $request, string $module)
    {
        return response()->json(Cache::get($request->query('cacheKey'), [
            'progress' => 0,
            'processed' => 0,
            'status' => 'pending',
        ]));
    }

    public function downloadExport(Request $request, string $module)
    {
        $filePath = $request->query('filePath');

        if (! Storage::disk('local')->exists($filePath)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($filePath);
    }

    /**
     * AJAX endpoint for the universal field.
     * Returns a JSON map [id => title] for the selected morph type.
     */
    public function getRelatedItems(Request $request, string $module)
    {
        $data = request()->all();
        $parentValue = $data['parentValue'] ?? null;
        $items = $this->relatedEntityFieldService->getEntities($parentValue);

        return response()->json($items);
    }
}
