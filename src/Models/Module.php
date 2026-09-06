<?php

namespace Nodex\Nexus\Models;

use Nodex\Nexus\Attributes\Module as ModuleAttr;
use Nodex\Nexus\Attributes\Requests;
use Nodex\Nexus\Attributes\Section;
use Nodex\Nexus\Attributes\TableFilter;
use Nodex\Nexus\Attributes\TableAction;
use Nodex\Nexus\Attributes\TableGroupAction;
use Nodex\Nexus\Attributes\Composer;
use Nodex\Nexus\Attributes\MethodResource;
use Nodex\Nexus\Attributes\Column;
use Nodex\Nexus\Attributes\Field;
use Nodex\Nexus\Attributes\Relation;
use Nodex\Nexus\Concerns\HasAttributeSchemaProperties;
use Illuminate\Database\Eloquent\Model;
use Nodex\Nexus\Services\ModuleManager;

#[ModuleAttr(
    name: 'modules',
    label: 'Керування модулями',
    icon: 'layers',
    group: 'Modules',
    showInMenu: true,
    isTree: false,
    menuResolver: null
)]
#[TableFilter(name: 'search', label: 'Search by Name', type: 'search')]
#[TableAction(name: 'edit', label: 'Edit', icon: 'edit', isConfirm: false)]
#[TableAction(name: 'delete', label: 'Delete', icon: 'delete', isConfirm: true)]
#[TableAction(name: 'restore', label: 'Restore', icon: 'restore', isConfirm: true)]
#[TableAction(name: 'deletePermanent', label: 'Delete permanent', icon: 'delete', isConfirm: true)]
#[TableGroupAction(name: 'deleteGroup', fieldName: 'delete')]
#[Section(name: 'information', column: 'right', type: 'columns_2', icon: 'cart')]
#[Section(name: 'relations', column: 'left', type: 'base')]
class Module extends Model
{
    use HasAttributeSchemaProperties;

    #[Column(label: 'ID', sortable: true)]
    protected $id;

    #[Column(label: 'Name', sortable: true)]
    protected $name;

    #[Column(label: 'Enabled', sortable: true, action: 'boolToggle', fieldName: 'is_enabled', actionConfirm: true)]
    protected $is_enabled;

    #[Column(label: 'Manage', customField: 'manage')]
    protected $manage;


    protected $table = 'nexus_modules';
    protected $fillable = ['name', 'is_enabled', 'is_system', 'config'];
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_system' => 'boolean',
        'config' => 'json',
    ];

    /**
     * Blocks turning off (or deleting) a system module's is_enabled flag —
     * uninstall() already guards is_system for the delete path, but the
     * boolToggle admin action (and anything else calling save() directly)
     * goes straight through Eloquent, bypassing that check. Without this, an
     * admin could disable the very module that manages module enable/disable
     * and lock themselves out of the screen that would undo it. Returning
     * false from an `updating`/`deleting` listener cancels the operation
     * silently, per Eloquent's own convention — no exception, the checkbox
     * just doesn't take.
     */
    protected static function booted(): void
    {
        static::updating(function (self $module) {
            if ($module->is_system && $module->isDirty('is_enabled') && ! $module->is_enabled) {
                return false;
            }
        });

        static::deleting(function (self $module) {
            if ($module->is_system) {
                return false;
            }
        });
    }

    public function getConfigAttribute($value)
    {
        $config = $value;
        if ($config) {
            $config = is_array($config) ? $config : json_decode($config, true);
        } else {
            // $this->name (not $this->getAttribute('name')) would hit the
            // declared protected $name property below instead of Eloquent's
            // magic accessor, always resolving to null.
            $config = ModuleManager::getModuleConfig($this->getAttribute('name'));
        }

        return \Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto::fromArray($config ?? []);
    }

    public function getRouteKeyName()
    {
        return 'name'; // або 'slug'
    }

    /**
     * Case-insensitive lookup by name — the row this table actually stores
     * can carry whatever casing `nexus:module:install {name}` happened to
     * be run with (folder/StudlyCase, e.g. 'Demo'), while `#[Module(name:)]`
     * (and so route()/route-param values built from a module config's own
     * ->name, e.g. 'demo') is always lowercase. MySQL's default collation
     * makes `WHERE name = 'demo'` match a 'Demo' row anyway, which is why
     * every {module}-bound route already worked in production — but that's
     * MySQL-collation behavior, not something this table's data guarantees,
     * and SQLite (this project's test suite, and now its CI matrix) compares
     * case-sensitively, so the exact same lookup genuinely fails there. Use
     * this instead of `Module::where('name', $x)` anywhere a caller can't
     * be sure which casing it has.
     */
    public static function findByName(string $name): ?self
    {
        return static::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    /**
     * Laravel calls this for implicit route-model-binding — every
     * {module}-typed route parameter (nexus.module.action, .import, .export,
     * ...) goes through here, not through resolveRouteBinding()'s default
     * exact-match `where($field, $value)`. Only the plain {module} case
     * (Nodex\Nexus\Models\Module type-hint, no explicit :column suffix) is
     * overridden — {module:id} or similar stays exact-match, same as core.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        return static::findByName($value);
    }

}
