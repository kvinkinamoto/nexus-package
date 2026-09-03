<?php

namespace Nodex\Nexus\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nodex\Nexus\Attributes\Column as ColumnAttr;
use Nodex\Nexus\Attributes\Composer as ComposerAttr;
use Nodex\Nexus\Attributes\Field as FieldAttr;
use Nodex\Nexus\Attributes\MethodResource as MethodResourceAttr;
use Nodex\Nexus\Attributes\Module as ModuleAttr;
use Nodex\Nexus\Attributes\Permission as PermissionAttr;
use Nodex\Nexus\Attributes\Relation as RelationAttr;
use Nodex\Nexus\Attributes\RepeaterField as RepeaterFieldAttr;
use Nodex\Nexus\Attributes\Requests as RequestsAttr;
use Nodex\Nexus\Attributes\Section as SectionAttr;
use Nodex\Nexus\Attributes\SectionColumn as SectionColumnAttr;
use Nodex\Nexus\Attributes\Setting as SettingAttr;
use Nodex\Nexus\Attributes\TableAction as TableActionAttr;
use Nodex\Nexus\Attributes\TableFilter as TableFilterAttr;
use Nodex\Nexus\Attributes\TableGroupAction as TableGroupActionAttr;
use Nodex\Nexus\Attributes\TableImport as TableImportAttr;
use Nodex\Nexus\Attributes\TableLens as TableLensAttr;
use Nodex\Nexus\Concerns\HasAttributeSchemaProperties;
use Nodex\Nexus\Dto\ModuleDtos\AjaxRelationConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\ColumnConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\LensConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\RelationConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\RepeaterFieldConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\SectionColumnConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\SectionConfigDto;
use Nodex\Nexus\Enums\AjaxModeEnum;
use Nodex\Nexus\Enums\RelationConfigParamsEnum;
use ReflectionClass;
use ReflectionMethod;

/**
 * Reads PHP 8 Attributes (#[Module], #[Field], #[Column], #[Relation], #[Section])
 * from an Eloquent Model class and builds a DefaultModuleConfigurationDto automatically.
 *
 * This is the core service that enables zero-boilerplate module creation.
 * Instead of writing a 170-line ModuleConfiguration.php, developers annotate
 * their Model directly and get a fully functional admin panel for free.
 *
 * Usage:
 *   $reader = app(AttributeSchemaReader::class);
 *   $dto = $reader->read(Article::class);
 *
 * The resulting DTO is fully compatible with the existing FormBuilder, TableBuilder,
 * and NexusController pipeline.
 */
class AttributeSchemaReader
{
    /**
     * Read a Model class and build a DefaultModuleConfigurationDto from its PHP 8 Attributes.
     *
     * @param  string  $modelClass  Fully-qualified Eloquent Model class name.
     * @return DefaultModuleConfigurationDto|null Returns null if the class has no #[Module] attribute.
     */
    public function read(string $modelClass): ?DefaultModuleConfigurationDto
    {
        if (! class_exists($modelClass)) {
            return null;
        }

        $reflection = new ReflectionClass($modelClass);

        $moduleAttrInstances = $reflection->getAttributes(ModuleAttr::class);
        if (empty($moduleAttrInstances)) {
            return null; // This model is not annotated as a Nexus module
        }

        /** @var ModuleAttr $moduleMeta */
        $moduleMeta = $moduleAttrInstances[0]->newInstance();

        $config = new DefaultModuleConfigurationDto;
        $this->applyModuleMeta($config, $moduleMeta, $modelClass);

        $this->processRequestsAttr($reflection, $config);
        $this->processPermissionAttrs($reflection, $config, $moduleMeta->name);
        $this->processTableAttrs($reflection, $config);
        $this->processComposerAttrs($reflection, $config);
        $this->processMethodResourceAttrs($reflection, $config);
        $this->processSettingAttrs($reflection, $config);
        $this->processSectionAttrs($reflection, $config);

        $this->guardAgainstUnshadowedProperties($reflection, $modelClass);
        $this->processPropertiesAndMethods($reflection, $config);
        $this->sortFieldsAndColumns($config);

        return $config;
    }

    /**
     * #[Module] itself: name, bound model, wizard/slideOver/livewire flags,
     * menu, tree flag, menu resolver.
     */
    private function applyModuleMeta(DefaultModuleConfigurationDto $config, ModuleAttr $moduleMeta, string $modelClass): void
    {
        $config->name($moduleMeta->name);

        // The annotated class binds to $moduleMeta->model when given (e.g. a
        // dedicated ModuleConfiguration class pointing at a vendor or shared
        // model). Otherwise, if the annotated class is itself an Eloquent
        // model, bind to it directly. If neither applies, leave the DTO's
        // model at its default sentinel — this module has no bound model.
        if ($moduleMeta->model !== null) {
            $config->model($moduleMeta->model);
        } elseif (is_subclass_of($modelClass, Model::class)) {
            $config->model($modelClass);
        }

        $config->requires = $moduleMeta->requires;
        $config->wizard = $moduleMeta->wizard;
        $config->slideOver = $moduleMeta->slideOver;
        $config->livewire = $moduleMeta->livewire;

        $config->menu()
            ->setLabel($moduleMeta->label ?: Str::headline($moduleMeta->name))
            ->setIcon($moduleMeta->icon)
            ->setParent($moduleMeta->group)
            ->setShow($moduleMeta->showInMenu);

        if ($moduleMeta->isTree) {
            $config->isTree();
        }
        if ($moduleMeta->menuResolver) {
            $config->resolver('menu', $moduleMeta->menuResolver);
        }
    }

    /**
     * #[Requests] — dedicated store/update FormRequest classes, if declared.
     */
    private function processRequestsAttr(ReflectionClass $reflection, DefaultModuleConfigurationDto $config): void
    {
        $requestsAttrInstances = $reflection->getAttributes(RequestsAttr::class);
        if (empty($requestsAttrInstances)) {
            return;
        }

        /** @var RequestsAttr $requestsMeta */
        $requestsMeta = $requestsAttrInstances[0]->newInstance();
        if ($requestsMeta->store) {
            $config->methodRequests['store'] = $requestsMeta->store;
        }
        if ($requestsMeta->update) {
            $config->methodRequests['update'] = $requestsMeta->update;
        }
    }

    /**
     * #[Permission] — custom permissions declared on the model, namespaced
     * under this module's name.
     */
    private function processPermissionAttrs(ReflectionClass $reflection, DefaultModuleConfigurationDto $config, string $moduleName): void
    {
        $permissionAttrs = $reflection->getAttributes(PermissionAttr::class);
        foreach ($permissionAttrs as $permAttrRef) {
            /** @var PermissionAttr $permMeta */
            $permMeta = $permAttrRef->newInstance();
            $permName = $moduleName.'_'.$permMeta->action;
            $label = $permMeta->label ?? Str::headline($permMeta->action);
            // Store in adminPanel permissions so the permission manager can register it
            $config->permissions->adminPanel[$permMeta->action] = $permName;
        }
    }

    /**
     * Table-level attributes: #[TableFilter], #[TableLens], #[TableAction],
     * #[TableGroupAction], #[TableImport].
     */
    private function processTableAttrs(ReflectionClass $reflection, DefaultModuleConfigurationDto $config): void
    {
        $tableFilterAttrs = $reflection->getAttributes(TableFilterAttr::class);
        foreach ($tableFilterAttrs as $filterAttrRef) {
            /** @var TableFilterAttr $filterMeta */
            $filterMeta = $filterAttrRef->newInstance();
            $config->table->filter($filterMeta->name, $filterMeta->label, $filterMeta->type);
        }

        $tableLensAttrs = $reflection->getAttributes(TableLensAttr::class);
        foreach ($tableLensAttrs as $lensAttrRef) {
            /** @var TableLensAttr $lensMeta */
            $lensMeta = $lensAttrRef->newInstance();
            $config->lenses[$lensMeta->name] = new LensConfigDto(
                name: $lensMeta->name,
                label: $lensMeta->label,
                conditions: $lensMeta->conditions,
                icon: $lensMeta->icon,
                columns: $lensMeta->columns,
                sort: $lensMeta->sort,
            );
        }

        $tableActionAttrs = $reflection->getAttributes(TableActionAttr::class);
        foreach ($tableActionAttrs as $actionAttrRef) {
            /** @var TableActionAttr $actionMeta */
            $actionMeta = $actionAttrRef->newInstance();
            if ($actionMeta->isMain) {
                $config->table->mainAction($actionMeta->name, $actionMeta->label, $actionMeta->icon, $actionMeta->isConfirm, $actionMeta->isActive);
            } else {
                $config->table->action($actionMeta->name, $actionMeta->label, $actionMeta->icon, $actionMeta->isConfirm, $actionMeta->isActive);
            }
        }

        $tableGroupActionAttrs = $reflection->getAttributes(TableGroupActionAttr::class);
        foreach ($tableGroupActionAttrs as $groupActionAttrRef) {
            /** @var TableGroupActionAttr $groupActionMeta */
            $groupActionMeta = $groupActionAttrRef->newInstance();
            $config->table->group($groupActionMeta->name, $groupActionMeta->fieldName);
        }

        $tableImportAttrs = $reflection->getAttributes(TableImportAttr::class);
        foreach ($tableImportAttrs as $importAttrRef) {
            /** @var TableImportAttr $importMeta */
            $importMeta = $importAttrRef->newInstance();
            $config->table->import($importMeta->name, $importMeta->label, $importMeta->icon, $importMeta->confirm, $importMeta->isActive);
        }
    }

    /**
     * #[Composer] — view composer classes registered against this module.
     */
    private function processComposerAttrs(ReflectionClass $reflection, DefaultModuleConfigurationDto $config): void
    {
        $composerAttrs = $reflection->getAttributes(ComposerAttr::class);
        foreach ($composerAttrs as $composerAttrRef) {
            /** @var ComposerAttr $composerMeta */
            $composerMeta = $composerAttrRef->newInstance();
            $config->composers[] = $composerMeta->class;
        }
    }

    /**
     * #[MethodResource] — API resource class (+ eager-load relations) bound
     * to a specific controller method.
     */
    private function processMethodResourceAttrs(ReflectionClass $reflection, DefaultModuleConfigurationDto $config): void
    {
        $methodResourceAttrs = $reflection->getAttributes(MethodResourceAttr::class);
        foreach ($methodResourceAttrs as $resourceAttrRef) {
            /** @var MethodResourceAttr $resourceMeta */
            $resourceMeta = $resourceAttrRef->newInstance();
            $config->methodResource[$resourceMeta->method] = [
                'resources' => [
                    'default' => $resourceMeta->resourceClass,
                ],
                'with' => $resourceMeta->with,
            ];
        }
    }

    /**
     * #[Setting] — module-level settings shown on the Settings admin screen.
     */
    private function processSettingAttrs(ReflectionClass $reflection, DefaultModuleConfigurationDto $config): void
    {
        $settingAttrs = $reflection->getAttributes(SettingAttr::class);
        foreach ($settingAttrs as $settingAttrRef) {
            /** @var SettingAttr $settingMeta */
            $settingMeta = $settingAttrRef->newInstance();
            $setting = $config->setting($settingMeta->name, $settingMeta->type, $settingMeta->label, $settingMeta->default);
            $setting->required($settingMeta->required);
            $setting->options($settingMeta->options);
            $setting->multiple($settingMeta->multiple);
            if ($settingMeta->comment !== null) {
                $setting->comment($settingMeta->comment);
            }
        }
    }

    /**
     * #[Section] (which column + optional tab each section lives in, deriving
     * sectionColumns from the unique columns declared) and #[SectionColumn]
     * (explicit width/tab overrides on top of that).
     */
    private function processSectionAttrs(ReflectionClass $reflection, DefaultModuleConfigurationDto $config): void
    {
        $sectionAttrs = $reflection->getAttributes(SectionAttr::class);

        // No #[Section] attributes at all — use the default two-column layout
        // inherited from DefaultModuleConfigurationDto::__construct().
        if (! empty($sectionAttrs)) {
            // Collect unique columns and their tabs
            $columnMap = []; // column_name => tab|null
            foreach ($sectionAttrs as $sAttr) {
                /** @var SectionAttr $s */
                $s = $sAttr->newInstance();
                $config->sections[$s->name] = new SectionConfigDto($s->name, $s->column, $s->type, $s->icon);
                if (! isset($columnMap[$s->column])) {
                    $columnMap[$s->column] = $s->tab;
                }
            }

            // Build sectionColumns from the unique columns found in sections
            $config->sectionColumns = [];
            foreach ($columnMap as $columnName => $tab) {
                $colDto = new SectionColumnConfigDto($columnName, 'col-lg-6');
                if ($tab) {
                    $colDto->tab($tab);
                    // Auto-register the tab if not already there
                    if (! isset($config->tabs[$tab])) {
                        $config->tab($tab)->label($tab);
                    }
                }
                $config->sectionColumns[$columnName] = $colDto;
            }
        }

        $sectionColumnAttrs = $reflection->getAttributes(SectionColumnAttr::class);
        foreach ($sectionColumnAttrs as $colAttrRef) {
            /** @var SectionColumnAttr $colMeta */
            $colMeta = $colAttrRef->newInstance();
            $colDto = $config->sectionColumn($colMeta->name);
            $colDto->class($colMeta->class);
            if ($colMeta->tab !== null) {
                $colDto->tab($colMeta->tab);
            }
        }
    }

    /**
     * #[Field]/#[Column]/#[Relation]/#[RepeaterField] off both declared
     * properties and public methods — one shared order index across both
     * passes (property declaration order, then method declaration order),
     * since #[Field(order:)]/#[Column(order:)] only overrides it via the
     * stable sort in sortFieldsAndColumns() afterwards.
     */
    private function processPropertiesAndMethods(ReflectionClass $reflection, DefaultModuleConfigurationDto $config): void
    {
        $fieldOrder = 0;
        $columnOrder = 0;

        foreach ($reflection->getProperties() as $property) {
            $fieldOrder = $this->processFieldAttr($property->getAttributes(FieldAttr::class), $property->getName(), $config, $fieldOrder);
            $columnOrder = $this->processColumnAttr($property->getAttributes(ColumnAttr::class), $property->getName(), $config, $columnOrder);

            // #[Relation] is occasionally placed on a property rather than the
            // real Eloquent relation method of the same name (e.g. Role/Permission's
            // $permissions/$roles/$users — the method comes from a Spatie trait,
            // not declared on the model itself, so there's nothing to attach the
            // attribute to). Only resolvable when the type is explicit: without a
            // method to reflect, there's no return-type hint to auto-detect from.
            $relationAttrs = $property->getAttributes(RelationAttr::class);
            if (! empty($relationAttrs)) {
                $method = $reflection->hasMethod($property->getName())
                    ? $reflection->getMethod($property->getName())
                    : null;
                $this->processRelationAttr($relationAttrs, $property->getName(), $config, $method);
            }
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $fieldAttrs = $method->getAttributes(FieldAttr::class);
            $relationAttrs = $method->getAttributes(RelationAttr::class);
            $columnAttrs = $method->getAttributes(ColumnAttr::class);

            if (empty($fieldAttrs)) {
                continue; // Only process methods that are explicitly annotated
            }

            $methodName = $method->getName();

            $fieldOrder = $this->processFieldAttr($fieldAttrs, $methodName, $config, $fieldOrder);
            $columnOrder = $this->processColumnAttr($columnAttrs, $methodName, $config, $columnOrder);

            // Stack of #[RepeaterField] — one column per instance, declaration order.
            $repeaterFieldAttrs = $method->getAttributes(RepeaterFieldAttr::class);
            if (! empty($repeaterFieldAttrs) && isset($config->form->fields[$methodName])) {
                $columns = [];
                foreach ($repeaterFieldAttrs as $repeaterAttrRef) {
                    /** @var RepeaterFieldAttr $repeaterMeta */
                    $repeaterMeta = $repeaterAttrRef->newInstance();
                    $columns[] = new RepeaterFieldConfigDto(
                        name: $repeaterMeta->name,
                        type: $repeaterMeta->type,
                        label: $repeaterMeta->label,
                        required: $repeaterMeta->required,
                        rules: is_string($repeaterMeta->rules) ? explode('|', $repeaterMeta->rules) : (array) $repeaterMeta->rules,
                        width: $repeaterMeta->width,
                        showWhen: $repeaterMeta->showWhen,
                        showWhenLogic: $repeaterMeta->showWhenLogic,
                    );
                }
                $config->form->fields[$methodName]->repeaterColumns = $columns;
            }

            // Build RelationConfigDto if #[Relation] is also present
            $this->processRelationAttr($relationAttrs, $methodName, $config, $method);
        }
    }

    /**
     * Applies explicit #[Field(order:)]/#[Column(order:)] on top of
     * declaration order. Stable sort (PHP 8+): entries with the default
     * order (0) keep their declaration-order position relative to each other.
     */
    private function sortFieldsAndColumns(DefaultModuleConfigurationDto $config): void
    {
        uasort($config->form->fields, fn ($a, $b) => $a->order <=> $b->order);
        uasort($config->table->columns, fn ($a, $b) => $a->order <=> $b->order);
    }

    /**
     * Checks whether a given model class has been annotated with #[Module].
     */
    public function hasModuleAttribute(string $modelClass): bool
    {
        if (! class_exists($modelClass)) {
            return false;
        }
        $reflection = new ReflectionClass($modelClass);

        return ! empty($reflection->getAttributes(ModuleAttr::class));
    }

    /**
     * Guard against D22: a declared property carrying #[Field]/#[Column]
     * permanently shadows Eloquent's __get()/__set() for that name, unless
     * the model uses HasAttributeSchemaProperties (which unsets the
     * declared property right after construction so Eloquent's magic
     * accessor takes over). Without it, admin forms/tables/API fields for
     * that property silently read/write nothing. See the trait's own
     * docblock for the full explanation.
     *
     * @throws \RuntimeException If a property-level #[Field]/#[Column] is found
     *                           without the trait — fails fast in development
     *                           rather than silently breaking the admin UI.
     */
    private function guardAgainstUnshadowedProperties(ReflectionClass $reflection, string $modelClass): void
    {
        // Only Eloquent models have a magic __get()/__set() to shadow in the first
        // place — #[Field]/#[Column] are also used on plain ModuleConfiguration
        // DTO classes (e.g. ActivityLog's, bound to a separate vendor model via
        // #[Module(model: ...)]) where this bug class cannot occur.
        if (! $reflection->isSubclassOf(Model::class)) {
            return;
        }

        if (in_array(HasAttributeSchemaProperties::class, class_uses_recursive($modelClass), true)) {
            return;
        }

        foreach ($reflection->getProperties() as $property) {
            if (! empty($property->getAttributes(FieldAttr::class)) || ! empty($property->getAttributes(ColumnAttr::class))) {
                throw new \RuntimeException(sprintf(
                    '%s::$%s carries #[Field]/#[Column] but %s does not use %s — the declared property will '
                    .'permanently shadow Eloquent\'s magic accessor for "%s", silently breaking admin forms/tables '
                    .'(see the trait\'s docblock, or memory "D22" for the full bug history). '
                    .'Add `use %s;` to the model.',
                    $modelClass,
                    $property->getName(),
                    $modelClass,
                    HasAttributeSchemaProperties::class,
                    $property->getName(),
                    class_basename(HasAttributeSchemaProperties::class),
                ));
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Process #[Field] attributes from a reflected property/method.
     *
     * @param  \ReflectionAttribute[]  $attrs
     * @return int Updated order counter
     */
    private function processFieldAttr(array $attrs, string $name, DefaultModuleConfigurationDto $config, int $order): int
    {
        if (empty($attrs)) {
            return $order;
        }

        /** @var FieldAttr $fieldMeta */
        $fieldMeta = $attrs[0]->newInstance();

        $label = $fieldMeta->label ?? Str::headline($name);
        $type = $fieldMeta->type === 'editor' ? 'text' : $fieldMeta->type;

        $defaultValue = $fieldMeta->default;
        if (is_array($defaultValue) && isset($defaultValue['class']) && class_exists($defaultValue['class'])) {
            $class = $defaultValue['class'];
            $params = $defaultValue['params'] ?? [];
            try {
                $defaultValue = new $class(...$params);
            } catch (\Throwable $e) {
                // Fallback to array if instantiation fails
            }
        }

        $dto = new FieldConfigDto(
            name: $name,
            type: $type,
            section: $fieldMeta->section,
            label: $label,
            default: $defaultValue,
            isRequired: $fieldMeta->required,
            isTranslate: $fieldMeta->translated,
            isEditor: $fieldMeta->editor || $fieldMeta->type === 'editor',
            enum: $fieldMeta->enum,
            actionName: $fieldMeta->action,
            actionField: $fieldMeta->actionField,
            isDisabled: is_bool($fieldMeta->disabled) ? $fieldMeta->disabled : true,
            disabledContext: is_string($fieldMeta->disabled) ? $fieldMeta->disabled : null,
            relationConfig: ! empty($fieldMeta->relationConfig) ? $fieldMeta->relationConfig : null,
            rules: is_string($fieldMeta->rules) ? explode('|', $fieldMeta->rules) : (array) $fieldMeta->rules,
            storeRules: is_string($fieldMeta->storeRules) ? explode('|', $fieldMeta->storeRules) : (array) $fieldMeta->storeRules,
            updateRules: is_string($fieldMeta->updateRules) ? explode('|', $fieldMeta->updateRules) : (array) $fieldMeta->updateRules,
            order: $fieldMeta->order,
            showWhen: $fieldMeta->showWhen,
            showWhenLogic: $fieldMeta->showWhenLogic,
            clearWhenHidden: $fieldMeta->clearWhenHidden,
            showInInfolist: $fieldMeta->showInInfolist,
        );

        // Handle 'view' type: store the blade path in userType
        if ($fieldMeta->type === 'view' && $fieldMeta->view) {
            $dto->userType = $fieldMeta->view;
        }

        $config->form->fields[$name] = $dto;

        return $order + 1;
    }

    /**
     * Process #[Column] attributes from a reflected property/method.
     *
     * @param  \ReflectionAttribute[]  $attrs
     * @return int Updated order counter
     */
    private function processColumnAttr(array $attrs, string $name, DefaultModuleConfigurationDto $config, int $order): int
    {
        if (empty($attrs)) {
            return $order;
        }

        /** @var ColumnAttr $colMeta */
        $colMeta = $attrs[0]->newInstance();

        $config->table->columns[$name] = new ColumnConfigDto(
            name: $name,
            label: $colMeta->label ?? Str::headline($name),
            sortable: $colMeta->sortable,
            action: $colMeta->action,
            fieldName: $colMeta->fieldName,
            actionConfirm: $colMeta->actionConfirm,
            customField: $colMeta->customField,
            tableDefault: $colMeta->tableDefault,
            order: $colMeta->order,
            searchable: $colMeta->searchable,
        );

        return $order + 1;
    }

    /**
     * Build a RelationConfigDto from #[Relation] attributes found on either a
     * relation method or a property of the same name (see the property loop's
     * comment above for why the latter exists). $method is used only to
     * auto-detect the relation type from a return-type hint when the
     * attribute doesn't specify one explicitly — a property with no matching
     * method has nothing to detect from, so an explicit type: is required.
     *
     * @param  \ReflectionAttribute[]  $relationAttrs
     */
    private function processRelationAttr(
        array $relationAttrs,
        string $name,
        DefaultModuleConfigurationDto $config,
        ?ReflectionMethod $method,
    ): void {
        if (empty($relationAttrs)) {
            return;
        }

        /** @var RelationAttr $relMeta */
        $relMeta = $relationAttrs[0]->newInstance();

        // Auto-detect type from return type hint if not explicitly set
        $relType = $relMeta->type
            ? $this->normalizeRelationType($relMeta->type)
            : ($method ? $this->detectRelationType($method) : RelationConfigParamsEnum::BELONGS_TO->value);

        // mode is read regardless of $relMeta->ajax: the Livewire relation
        // field (livewire/field_types/relation.blade.php) is always
        // ajax-driven — $ajax only gates the *legacy* non-Livewire field's
        // plain-select vs Choices.js choice — so a 'load' vs 'search'
        // ajaxMode without ajax: true still needs to reach ModuleForm.
        $ajaxConfig = new AjaxRelationConfigDto;
        $ajaxConfig->mode(
            $relMeta->ajaxMode === 'load'
                ? AjaxModeEnum::LOAD
                : AjaxModeEnum::SEARCH
        );
        if ($relMeta->ajax) {
            $ajaxConfig->enable(true);
            if ($relMeta->ajaxResource) {
                $ajaxConfig->resource($relMeta->ajaxResource);
            }
        }

        $config->relations->is_available[$name] = new RelationConfigDto(
            type: $relType,
            relationName: $name,
            isRequired: $relMeta->required,
            showField: $relMeta->show,
            ajaxConfig: $ajaxConfig,
            relatedModule: $relMeta->relatedModule,
            showFieldFallback: $relMeta->showFallback,
        );
    }

    /**
     * Normalize a user-provided relation type string to a RelationConfigParamsEnum value.
     */
    private function normalizeRelationType(string $type): string
    {
        return match (strtolower($type)) {
            'belongsto' => RelationConfigParamsEnum::BELONGS_TO->value,
            'hasmany' => RelationConfigParamsEnum::HAS_MANY->value,
            'hasone' => RelationConfigParamsEnum::HAS_ONE->value,
            'belongstomany' => RelationConfigParamsEnum::BELONGS_TO_MANY->value,
            default => $type,
        };
    }

    /**
     * Attempt to detect the Eloquent relation type from a method's return type hint.
     * Falls back to 'belongsTo' if it cannot be determined.
     */
    private function detectRelationType(ReflectionMethod $method): string
    {
        $returnType = $method->getReturnType();
        if (! $returnType) {
            return RelationConfigParamsEnum::BELONGS_TO->value;
        }

        $typeName = $returnType->getName();

        return match (true) {
            str_ends_with($typeName, 'HasMany') => RelationConfigParamsEnum::HAS_MANY->value,
            str_ends_with($typeName, 'HasOne') => RelationConfigParamsEnum::HAS_ONE->value,
            str_ends_with($typeName, 'BelongsToMany') => RelationConfigParamsEnum::BELONGS_TO_MANY->value,
            default => RelationConfigParamsEnum::BELONGS_TO->value,
        };
    }
}
