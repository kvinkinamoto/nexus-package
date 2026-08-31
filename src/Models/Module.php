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

    protected static function booted(): void
    {

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

}
