<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Dto\ModuleDtos\SectionConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\SettingConfigDto;
use Nodex\Nexus\Enums\AdminAvailableFilterEnum;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;
use Nodex\Nexus\Enums\AvailableActionEnum;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\PathManager;

class DefaultModuleConfigurationDto extends \stdClass
{
    public string $name = 'ModuleName';
    public string $model = Model::class;
    public array $methodRequests;
    public MenuConfigDto $menu;
    public TableConfigDto $table;
    public FormConfigDto $form;
    public PermissionDto $permissions;
    public RelationsConfigDto $relations;
    public array $sectionColumns;
    public array $sections;
    public array $methodResource;
    public array $tabs;
    public bool $isTree = false;
    public bool $wizard = false;
    public bool $slideOver = false;
    public bool $livewire = false;
    public array $lenses = [];
    public array $duplicatableRelations = [];
    public array $settings = [];
    public array $composers = [];
    public array $resolvers = [];
    public array $requires = [];

    public function __construct()
    {
        $this->methodRequests = [
            AvailableActionEnum::STORE_ACTION->value => FormRequest::class,
            AvailableActionEnum::UPDATE_ACTION->value => FormRequest::class,
        ];

        $this->menu = new MenuConfigDto(
            name: 'ModuleName',
        );

        $this->table = new TableConfigDto(
            columns: [
                'id' => new ColumnConfigDto('id', 'ID', true),
            ],
            filters: [
//                'name' => new FilterConfigDto('name', 'Search by Name', AdminAvailableFilterEnum::FILTER_SEARCH->value),
//                'trashed' => new FilterConfigDto('trashed', 'Trashed', AdminAvailableFilterEnum::FILTER_TRASHED->value),
            ],

            exports: [
                'export' => new ExportConfigDto('export', 'Export'),
            ],

            mainActions: [
                'create' => new ActionConfigDto('create', 'Create'),
            ],

            actions: [
                'view' => new ActionConfigDto('view', 'View', 'eye', false),
                'edit' => new ActionConfigDto('edit', 'Edit', 'edit', false),
                'delete' => new ActionConfigDto('delete', 'Delete', 'delete', true),
                'restore' => new ActionConfigDto('restore', 'Restore', 'restore', true),
                'deletePermanent' => new ActionConfigDto('deletePermanent', 'Delete permanent', 'delete', true),
//                'duplicate' => new ActionConfigDto('duplicate', 'duplicate', 'copy', true),
            ],
            actionGroup: [
                'deleteGroup' => new ActionGroupConfigDto('deleteGroup', 'delete', true),
//                'duplicateGroup' => new ActionGroupConfigDto('duplicateGroup', 'duplicate', true),
//                'restoreGroup' => new ActionGroupConfigDto('restoreGroup', 'restore'),
//                'publishGroup' => new ActionGroupConfigDto('publishGroup', 'is_published'),
//                'unpublishGroup' => new ActionGroupConfigDto('unpublishGroup', 'unpublish'),
            ],
        );

        $this->form = new FormConfigDto([
            //            'name' => new FieldConfigDto('name', 'string', 'Default section', 'name'),
//            new FieldConfigDto('description', 'textarea', 'Description', 'details'),
//            new FieldConfigDto('price', 'number', 'Price', 'details'),
//            new FieldConfigDto('stock', 'number', 'Stock', 'details'),
//            new FieldConfigDto('image', 'image', 'Product Image', 'media'),
////            new FieldConfigDto('status', 'enum', 'Status', 'details', ProductStatus::class, ProductStatus::ACTIVE),
//            new FieldConfigDto('categories', 'relation', 'Categories', 'relations'),
        ]);

        $this->relations = new RelationsConfigDto(
            available: [
                //               'parent' => new RelationConfigDto(RelationConfigParamsEnum::BELONGS_TO_MANY->value, 'parent', false),
            ],
        );

        $this->sectionColumns = [
            'left' => new SectionColumnConfigDto('left', 'col-lg-4'),
            'right' => new SectionColumnConfigDto('right', 'col-lg-8'),
        ];

        $this->sections = [
            'information' => new SectionConfigDto('information', 'right', 'columns_2', 'cart'),
            'relations' => new SectionConfigDto('relations', 'left', 'base'),
        ];

        $this->methodResource = [];

        $this->tabs = [];

        $this->permissions = new PermissionDto(
            adminPanel: [],
            api: [],
            frontend: []
        );
    }

    public function name(string $name): self
    {
        $this->name = $name;
        $this->menu->setName($name);

        if (config('nexus.permissions.generate_default', true)) {
            foreach (\Nodex\Nexus\Enums\AvailableActionEnum::cases() as $action) {
                if (!isset($this->permissions->adminPanel[$action->value])) {
                    $this->permissions->adminPanel[$action->value] = "{$name}_{$action->value}";
                }
            }
        }

        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function menu(?\Closure $callback = null): self|MenuConfigDto
    {
        if ($callback) {
            $callback($this->menu);
            return $this;
        }
        return $this->menu;
    }

    public function table(?\Closure $callback = null): self|TableConfigDto
    {
        if ($callback) {
            $callback($this->table);
            return $this;
        }
        return $this->table;
    }

    public function form(?\Closure $callback = null): self|FormConfigDto
    {
        if ($callback) {
            $callback($this->form);
            return $this;
        }
        return $this->form;
    }

    public function relation(string $name): RelationConfigDto
    {
        if (!isset($this->relations->is_available[$name])) {
            $this->relations->is_available[$name] = new RelationConfigDto('', relationName: $name);
        }
        return $this->relations->is_available[$name];
    }

    public function section(string $name): SectionConfigDto
    {
        if (!isset($this->sections[$name])) {
            $this->sections[$name] = new SectionConfigDto($name, 'right');
        }
        return $this->sections[$name];
    }

    public function sectionColumn(string $name): SectionColumnConfigDto
    {
        if (!isset($this->sectionColumns[$name])) {
            $this->sectionColumns[$name] = new SectionColumnConfigDto($name, 'col-lg-4');
        }
        return $this->sectionColumns[$name];
    }

    public function tab(string $name): TabConfigDto
    {
        if (!isset($this->tabs[$name])) {
            $this->tabs[$name] = new TabConfigDto($name, $name);
        }
        return $this->tabs[$name];
    }

    public function duplicatableRelations(array $relations): self
    {
        $this->duplicatableRelations = $relations;
        return $this;
    }

    public function isTree(bool $isTree = true): self
    {
        $this->isTree = $isTree;
        return $this;
    }

    public function setting(string $name, string $type, ?string $label = null, mixed $default = null): SettingConfigDto
    {
        if (!isset($this->settings[$name])) {
            $this->settings[$name] = new SettingConfigDto($name, $type, $label, $default);
        }
        return $this->settings[$name];
    }

    public function resolver(string $name, string $resolverClass): self
    {
        $this->resolvers[$name] = $resolverClass;
        return $this;
    }

    public static function fromArray(array|object|null $data): self
    {
        if ($data instanceof self) {
            return $data;
        }

        $objData = (object) ($data ?? new \stdClass());
        $instance = new self();

        if ($data === null) {
            return $instance;
        }

        $instance->name = $objData->name ?? $instance->name;
        $instance->model = $objData->model ?? $instance->model;

        if (isset($objData->methodRequests)) {
            $instance->methodRequests = (array) $objData->methodRequests;
        }

        if (isset($objData->menu)) {
            $instance->menu = MenuConfigDto::fromArray($objData->menu);
        }

        if (isset($objData->table)) {
            $instance->table = TableConfigDto::fromArray($objData->table);
        }

        if (isset($objData->form)) {
            $formData = (object) $objData->form;
            $orderedFields = new \stdClass();
            if (isset($formData->order)) {
                $fields = (object) ($formData->fields ?? new \stdClass());
                foreach ($formData->order as $key) {
                    if (isset($fields->{$key})) {
                        $orderedFields->{$key} = (object) $fields->{$key};
                    }
                }
                $formData->fields = $orderedFields;
            }

            $instance->form = FormConfigDto::fromArray($formData);
        }

        if (isset($objData->relations)) {
            $instance->relations = RelationsConfigDto::fromArray($objData->relations);
        }

        if (isset($objData->sectionColumns)) {
            $instance->sectionColumns = [];
            foreach ($objData->sectionColumns as $key => $col) {
                $objCol = (object) $col;
                $name = $objCol->name ?? $key;
                $instance->sectionColumns[$name] = SectionColumnConfigDto::fromArray($objCol);
            }
        }

        if (isset($objData->sections)) {
            $instance->sections = [];
            foreach ($objData->sections as $key => $sec) {
                $objSec = (object) $sec;
                $name = $objSec->name ?? $key;
                $instance->sections[$name] = SectionConfigDto::fromArray($objSec);
            }
        }

        if (isset($objData->tabs)) {
            $instance->tabs = [];
            foreach ($objData->tabs as $key => $tab) {
                $objTab = (object) $tab;
                $name = $objTab->name ?? $key;
                $instance->tabs[$name] = TabConfigDto::fromArray($objTab);
            }
        }

        if (isset($objData->settings)) {
            $instance->settings = [];
            foreach ($objData->settings as $key => $setting) {
                $objSetting = (object) $setting;
                $name = $objSetting->name ?? $key;
                if (!isset($objSetting->name)) {
                    $objSetting->name = $name;
                }
                $instance->settings[$name] = SettingConfigDto::fromArray($objSetting);
            }
        }

        if (isset($objData->duplicatableRelations)) {
            $instance->duplicatableRelations = (array) $objData->duplicatableRelations;
        }

        if (isset($objData->isTree)) {
            $instance->isTree = (bool) $objData->isTree;
        }

        if (isset($objData->wizard)) {
            $instance->wizard = (bool) $objData->wizard;
        }

        if (isset($objData->slideOver)) {
            $instance->slideOver = (bool) $objData->slideOver;
        }

        if (isset($objData->livewire)) {
            $instance->livewire = (bool) $objData->livewire;
        }

        if (isset($objData->lenses)) {
            $instance->lenses = [];
            foreach ($objData->lenses as $key => $lens) {
                $objLens = (object) $lens;
                $name = $objLens->name ?? $key;
                $instance->lenses[$name] = LensConfigDto::fromArray($objLens);
            }
        }

        if (isset($objData->resolvers)) {
            $instance->resolvers = (array) $objData->resolvers;
        }

        $permissions = self::normalize($objData->permissions ?? []);
        foreach ($permissions as $key => $permission) {
            $instance->permissions->{$key} = $permission;
        }

        if (isset($objData->methodResource)) {
            $instance->methodResource = (array) $objData->methodResource;
        }
        return $instance;
    }

    public function getModuleManager()
    {
        $className = get_class($this);

        // If it's the base class, try to derive from the model
        if ($className === self::class && $this->model && $this->model !== Model::class) {
            $modelClass = $this->model;
            // App\Nexus\Modules\ShopAttribute\Models\ShopAttribute -> App\Nexus\Modules\ShopAttribute\Services\ModuleManager
            $moduleManagerClass = str_replace('\\Models\\', '\\Services\\', $modelClass);
            $parts = explode('\\', $moduleManagerClass);
            array_pop($parts);
            $moduleManagerClass = implode('\\', $parts) . '\ModuleManager';

            if (class_exists($moduleManagerClass)) {
                return app()->make($moduleManagerClass);
            }
        }

        $moduleManagerClass = str_replace('ModuleConfiguration', 'Services\ModuleManager', $className);

        if (class_exists($moduleManagerClass)) {
            return app()->make($moduleManagerClass);
        }

        return app()->make(ModuleManager::class);
    }

    public function getPathManager(): PathManager
    {
        return app(PathManager::class);
    }

    public static function normalize(mixed $value): array
    {
        if ($value instanceof \stdClass) {
            return json_decode(json_encode($value), true);
        }

        return (array) $value;
    }
}
