<?php

namespace Nodex\Nexus\Dto\ModuleDtos;
 
use Nodex\Nexus\Enums\AvailableActionEnum;

class FieldConfigDto extends \stdClass
{

    public function __construct(
        public string $name,
        public string $type,
        public string $section = 'default',
        public ?string $label = null,
        public mixed $default = null,
        public bool $isRequired = true,
        public bool $isTranslate = false,
        public bool $isEditor = false,
        public ?string $enum = null,
        public ?string $userType = null,
        public ?string $actionName = null,
        public ?string $actionField = null,
        public ?array $relationConfig = null,
        public bool $isDisabled = false,
        public ?string $disabledContext = null,
        public array $rules = [],
        public array $storeRules = [],
        public array $updateRules = [],
        public int $order = 0,
        public array $showWhen = [],
        public string $showWhenLogic = 'and',
        public bool $clearWhenHidden = false,
        /** @var RepeaterFieldConfigDto[] */
        public array $repeaterColumns = [],
        public bool $showInInfolist = true,
        /**
         * Mirrors #[Field(apiExpose:)] — see AttributeSchemaReader::processFieldAttr(),
         * the only place this is actually set from the attribute. Read by
         * Services/GraphQL/SchemaBuilder to decide which fields get a
         * GraphQL type; NexusResource additionally re-reflects the raw
         * #[Field] attribute itself rather than reading this DTO property,
         * so keep both in sync if this ever changes.
         */
        public bool $apiExpose = false,
    ) {
        $this->label = $label ?? ucfirst($this->name);
    }

    /**
     * Aliases for properties several field-type partials read under the
     * "wrong" name (defaultValue instead of default, customFiledType —
     * a typo — instead of userType). Only fires for genuinely undefined
     * property reads: extends \stdClass, so a real dynamic write to either
     * name still wins over this alias.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'defaultValue' => $this->default,
            'customFiledType' => $this->userType,
            default => null,
        };
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function section(string $section): self
    {
        $this->section = $section;
        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->isRequired = $required;
        return $this;
    }

    public function translate(bool $translate = true): self
    {
        $this->isTranslate = $translate;
        return $this;
    }

    public function editor(bool $editor = true): self
    {
        $this->isEditor = $editor;
        return $this;
    }

    public function enum(string $enum): self
    {
        $this->enum = $enum;
        return $this;
    }

    public function userType(string $userType): self
    {
        $this->userType = $userType;
        return $this;
    }

    /**
     * @param  array  $showWhen  Normalized conditions — see Services/FieldVisibilityEvaluator.php.
     */
    public function showWhen(array $showWhen, string $logic = 'and'): self
    {
        $this->showWhen = $showWhen;
        $this->showWhenLogic = $logic;
        return $this;
    }

    public function clearWhenHidden(bool $clear = true): self
    {
        $this->clearWhenHidden = $clear;
        return $this;
    }

    public function action(string $name, ?string $field = null): self
    {
        $this->actionName = $name;
        $this->actionField = $field;
        return $this;
    }

    public function disabled(bool $disabled = true, string|AvailableActionEnum|null $context = null): self
    {
        $this->isDisabled = $disabled;
        $this->disabledContext = $context instanceof AvailableActionEnum ? $context->value : $context;

        return $this;
    }

    public function isDisabledForAction(?string $action): bool
    {
        if (!$this->isDisabled) {
            return false;
        }

        if (!$this->disabledContext) {
            return true;
        }

        if ($this->disabledContext === AvailableActionEnum::CREATE_ACTION->value) {
            return in_array($action, [
                AvailableActionEnum::CREATE_ACTION->value,
                AvailableActionEnum::STORE_ACTION->value
            ]);
        }

        if ($this->disabledContext === AvailableActionEnum::EDIT_ACTION->value) {
            return in_array($action, [
                AvailableActionEnum::EDIT_ACTION->value,
                AvailableActionEnum::UPDATE_ACTION->value
            ]);
        }

        return false;
    }

    public static function fromArray(array|\stdClass $field)
    {
        $field = (object) $field;
        return new self(
            name: $field->name,
            type: $field->type,
            section: $field->section ?? 'default',
            label: $field->label ?? null,
            default: $field->default ?? null,
            isRequired: $field->isRequired ?? true,
            isTranslate: $field->isTranslate ?? false,
            isEditor: $field->isEditor ?? false,
            enum: $field->enum ?? null,
            userType: $field->userType ?? null,
            actionName: $field->actionName ?? null,
            actionField: $field->actionField ?? null,
            isDisabled: $field->isDisabled ?? false,
            disabledContext: $field->disabledContext ?? null,
            rules: $field->rules ?? [],
            storeRules: $field->storeRules ?? [],
            updateRules: $field->updateRules ?? [],
            order: $field->order ?? 0,
            showWhen: $field->showWhen ?? [],
            showWhenLogic: $field->showWhenLogic ?? 'and',
            clearWhenHidden: $field->clearWhenHidden ?? false,
            showInInfolist: $field->showInInfolist ?? true,
            apiExpose: $field->apiExpose ?? false,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getEnum(): ?string
    {
        return $this->enum;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function toArray(): array
    {
        $array = [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label,
            'section' => $this->section,
        ];
        if ($this->enum)
            $array['enum'] = $this->enum;
        if ($this->default)
            $array['default'] = $this->default;
        if ($this->relationConfig)
            $array['relationConfig'] = $this->relationConfig;
        $array['isDisabled'] = $this->isDisabled;
        $array['disabledContext'] = $this->disabledContext;
        $array['order'] = $this->order;
        return $array;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isTranslate(): bool
    {
        return $this->isTranslate;
    }

    public function isEditor(): bool
    {
        return $this->isEditor;
    }

    public function getActionName(): ?string
    {
        return $this->actionName;
    }

    public function getActionField(): ?string
    {
        return $this->actionField;
    }

    /**
     * Redirects this field to a type name registered via
     * FieldTypeRegistry::registerView()/registerClass()/registerCallback()
     * (see Services/FieldTypeRegistry.php). Resolution — module override,
     * then the registry, then a built-in partial — happens the same way
     * as for any other $field->type string; nothing special about "custom".
     */
    public function customType(string $typeName): self
    {
        $this->type = $typeName;
        return $this;
    }

    public function relationConfig(array $config): self
    {
        $this->relationConfig = $config;
        return $this;
    }

    /**
     * Set this field as a View Slot — renders an arbitrary Blade template
     * at this field's position inside the admin form section.
     *
     * The Blade template receives: $model, $field, $module, $formData.
     *
     * Example:
     *   $form->field('price_preview', 'view', 'settings')->view('shopProduct::admin.components.price-preview');
     *
     * Or via PHP 8 Attribute on the Model:
     *   #[Field(type: 'view', section: 'settings', view: 'shopProduct::admin.components.price-preview')]
     *   public string $price_preview;
     *
     * @param  string  $viewPath  Blade view namespace path, e.g. 'myModule::admin.components.my-widget'
     */
    public function view(string $viewPath): self
    {
        $this->type = 'view';
        $this->userType = $viewPath;
        return $this;
    }

    /**
     * @deprecated Not the Livewire migration's own field pipeline — see
     * Livewire\ModuleForm and livewire/field_types/*.blade.php instead,
     * which dispatch on $field->type directly ('string' etc.), no
     * 'livewire' type indirection. This setter has no matching field_types
     * partial in either legacy theme and always falls through to the
     * "unknown field type" banner there. Prefer ->view() for a one-off
     * custom field render, or a real 'livewire'-type FieldTypeRegistry
     * entry if this project ever needs to embed an arbitrary sub-component
     * inside a still-legacy (non-#[Module(livewire:)]) form.
     *
     * @param  string  $component  Livewire component class or alias, e.g. 'admin.map-picker'
     * @param  array   $params     Parameters to pass to the component
     */
    public function livewire(string $component, array $params = []): self
    {
        $this->type = 'livewire';
        $this->userType = $component;
        $this->relationConfig = $params ?: null;
        return $this;
    }
}

