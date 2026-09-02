<?php

namespace Nodex\Nexus\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Nodex\Nexus\Events\ModuleActionExecuted;
use Nodex\Nexus\Livewire\Concerns\CallsLegacyActionMethods;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\Actions\Admin\BoolToggleActionMethod;
use Nodex\Nexus\Services\Actions\Admin\CallGroupActionMethod;
use Nodex\Nexus\Services\Actions\Admin\DeleteActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\DeleteActionMethod;
use Nodex\Nexus\Services\Actions\Admin\DeletePermanentActionMethod;
use Nodex\Nexus\Services\Actions\Admin\DuplicateActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\DuplicateActionMethod;
use Nodex\Nexus\Services\Actions\Admin\PublishActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\RestoreActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\RestoreActionMethod;
use Nodex\Nexus\Services\Actions\Admin\UnpublishActionGroupMethod;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\TableBuilder;

/**
 * Generic reactive replacement for pages/index.blade.php's sort/paginate/
 * filter/bulk-action full-page-reload cycle. Parameterized by module name so
 * one component serves every #[Module(livewire: true)] module. Reuses
 * TableBuilder and the same *ActionGroupMethod/BoolToggleActionMethod
 * services the legacy NexusController::action() dispatches to — Livewire
 * replaces only the transport and rendering, not the business logic.
 *
 * Mirrors NexusController::action()'s branch order for group actions: a few
 * names (deleteGroup, restoreGroup, ...) have a dedicated method on the
 * controller rather than falling through to the generic actionGroup config,
 * so this component keeps the same lookup table rather than treating every
 * group action identically.
 */
class ModuleTable extends Component
{
    use CallsLegacyActionMethods;

    public string $moduleName;

    public ?string $sort = null;

    public array $filter = [];

    public int $page = 1;

    public ?int $perPage = null;

    public array $selected = [];

    /** Active #[TableLens] name, or null for "all records" — see selectLens(). */
    public ?string $lens = null;

    /** Whether the slide-over panel (#[Module(slideOver: true)]) is open. */
    public bool $slideOverOpen = false;

    /** Id of the record being edited in the slide-over panel, if open. */
    public ?string $slideOverId = null;

    private ?Module $moduleCache = null;

    private const BUILT_IN_GROUP_ACTIONS = [
        'deleteGroup' => DeleteActionGroupMethod::class,
        'restoreGroup' => RestoreActionGroupMethod::class,
        'publishGroup' => PublishActionGroupMethod::class,
        'unpublishGroup' => UnpublishActionGroupMethod::class,
        'duplicateGroup' => DuplicateActionGroupMethod::class,
    ];

    /**
     * Single-record row actions (see pages/tableRow.blade.php's
     * $tableData['actions'] loop) that this component can run directly.
     * 'edit' isn't here — it either opens the slide-over (openSlideOver())
     * or is a plain link to editLivewire.blade.php, never a Livewire call.
     */
    private const SINGLE_RECORD_ACTIONS = [
        'delete' => DeleteActionMethod::class,
        'restore' => RestoreActionMethod::class,
        'deletePermanent' => DeletePermanentActionMethod::class,
        'duplicate' => DuplicateActionMethod::class,
    ];

    public function mount(string $moduleName): void
    {
        $this->moduleName = $moduleName;

        abort_unless(ModuleManager::checkPermission('index', $this->resolveModule()), 403);
    }

    /**
     * Any change under `filter.*` should reset pagination, same as the
     * legacy full-reload filter form always landing back on page 1.
     */
    public function updated(string $name): void
    {
        if ($name === 'filter' || str_starts_with($name, 'filter.')) {
            $this->page = 1;
        }
    }

    public function sortBy(string $column): void
    {
        // Descending first: for id-like columns the natural (unsorted) row
        // order already matches ascending, so an asc-first cycle made the
        // first click look like a no-op.
        $this->sort = match ($this->sort) {
            '-'.$column => $column,
            $column => null,
            default => '-'.$column,
        };
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Livewire equivalent of pages/index.blade.php's lens tabs, which are
     * plain links carrying '?lens={name}' — TableBuilder::build() reads that
     * same key regardless of whether it arrives via a real query string or
     * (as here) a plain array, see tableData() below.
     */
    public function selectLens(?string $lensName): void
    {
        $this->lens = $lensName;
        $this->page = 1;
    }

    public function toggleSelectAll(bool $checked): void
    {
        $this->selected = $checked
            ? collect($this->tableData()['data']->items())->map(fn ($item) => (string) $item->id)->all()
            : [];
    }

    public function toggleBool(string $id, string $fieldName): void
    {
        $module = $this->resolveModule();
        abort_unless(ModuleManager::checkPermission('boolToggle', $module), 403);

        $this->withRealRedirector(fn () => BoolToggleActionMethod::handle(
            $this->makeFormRequest(['fieldName' => $fieldName]),
            $module->config,
            $id
        ));

        event(new ModuleActionExecuted($module->name, 'boolToggle', id: $id));
    }

    /**
     * Mirrors tableRow.blade.php's $isSlideOverEdit branch: instead of a
     * js-slideover-trigger button opening an <iframe> onto editLivewire's
     * full page, this mounts ModuleForm directly inside the panel (see
     * module-table.blade.php's offcanvas markup) — no iframe, no separate
     * HTTP round trip for the panel's own content.
     */
    public function openSlideOver(string $id): void
    {
        abort_unless(ModuleManager::checkPermission('edit', $this->resolveModule()), 403);

        $this->slideOverId = $id;
        $this->slideOverOpen = true;
    }

    public function closeSlideOver(): void
    {
        $this->slideOverOpen = false;
        $this->slideOverId = null;
    }

    /**
     * The embedded ModuleForm dispatches this on both a successful save and
     * a cancel (see ModuleForm::save()/cancel()) — either way the panel
     * should close and the table should reflect current data.
     */
    #[On('nexus-module-form-saved')]
    public function onFormSaved(): void
    {
        $this->closeSlideOver();
    }

    /**
     * Runs a single-record action (delete/restore/duplicate/deletePermanent)
     * from a table row — the Livewire equivalent of tableRow.blade.php's
     * per-row <form method="POST" action="...action/{name}/{id}">. 'edit' is
     * handled separately (openSlideOver() or a plain link), never through here.
     */
    public function runAction(string $actionName, string $id): void
    {
        $module = $this->resolveModule();
        abort_unless(ModuleManager::checkPermission($actionName, $module), 403);

        $handlerClass = self::SINGLE_RECORD_ACTIONS[$actionName] ?? null;
        abort_if($handlerClass === null, 404);

        $this->withRealRedirector(fn () => $handlerClass::handle(
            $this->makeFormRequest([]),
            $module->config,
            $id
        ));

        event(new ModuleActionExecuted($module->name, $actionName, id: $id));
    }

    public function runGroupAction(string $actionName): void
    {
        $module = $this->resolveModule();
        abort_unless(ModuleManager::checkPermission($actionName, $module), 403);

        $moduleConfig = $module->config;
        $request = $this->makeFormRequest(['items' => $this->selected]);

        $this->withRealRedirector(function () use ($actionName, $request, $moduleConfig) {
            if (isset(self::BUILT_IN_GROUP_ACTIONS[$actionName])) {
                $handlerClass = self::BUILT_IN_GROUP_ACTIONS[$actionName];
                $handlerClass::handle($request, $moduleConfig);
            } else {
                $actionGroupConfig = $moduleConfig->table->actionGroup[$actionName] ?? null;
                abort_if($actionGroupConfig === null, 404);
                CallGroupActionMethod::handle($request, $moduleConfig, $actionGroupConfig);
            }
        });

        event(new ModuleActionExecuted($module->name, $actionName, ids: $this->selected));
        $this->selected = [];
    }

    public function render()
    {
        return view('nexus::'.config('nexus.template').'.livewire.module-table', [
            'module' => $this->resolveModule(),
            'tableData' => $this->tableData(),
        ]);
    }

    private function tableData(): array
    {
        return app(TableBuilder::class)->build($this->resolveModule(), [
            'sort' => $this->sort,
            'filter' => $this->filter,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'lens' => $this->lens,
        ], false, auth()->id());
    }

    private function resolveModule(): Module
    {
        return $this->moduleCache ??= Module::where('name', $this->moduleName)->firstOrFail();
    }
}
