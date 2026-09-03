{{--
    Single field's type dispatch, factored out of module-form.blade.php so
    both the flat (non-wizard) loop and the per-step wizard loop share one
    copy. Expects $field (FieldConfigDto) in scope.
--}}
@php($moduleNamespace = \Illuminate\Support\Str::lcfirst($module->name))
@if(View::exists($moduleNamespace . '::admin.livewire_field_types.' . $field->type))
    {{--
        Module-scoped override/custom-type hook, same "module override wins"
        convention already used for repeater cells (field_types/repeater.blade.php).
        A module can drop {module}::admin.livewire_field_types.{type}.blade.php
        to either add a brand new type (nothing below matches it) or override
        a built-in one (e.g. 'phone') for that module only — every other
        module has no such view, so View::exists() is false for them and
        behavior is unchanged.
    --}}
    @include($moduleNamespace . '::admin.livewire_field_types.' . $field->type, ['field' => $field])
@elseif($field->type === 'string')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.string', ['field' => $field])
@elseif($field->type === 'text')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.text', ['field' => $field])
@elseif($field->type === 'number')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.number', ['field' => $field])
@elseif($field->type === 'boolean')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.boolean', ['field' => $field])
@elseif($field->type === 'enum')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.enum', ['field' => $field])
@elseif($field->type === 'datetime')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.datetime', ['field' => $field])
@elseif($field->type === 'image' && empty($moduleConfig->relations->is_available[$field->name] ?? null))
    {{--
        Single elFinder-picked path (ShopCategory's `image`/`horizontalImage`
        — no #[Relation] attribute). A field also carrying #[Relation] (e.g.
        `images`, a morphMany gallery) is a different, not-yet-supported
        multi-image case — ModuleForm::mount() already routes its value
        through the relation branches instead, so it falls through to
        unsupported.blade.php here rather than crashing this partial on a
        non-string $value.
    --}}
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.image', ['field' => $field])
@elseif($field->type === 'relation')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.relation', ['field' => $field])
@elseif($field->type === 'wishlistable_type_field')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.wishlistable_type_field', ['field' => $field])
@elseif($field->type === 'causer')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.causer', ['field' => $field])
@elseif($field->type === 'json')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.json', ['field' => $field])
@elseif($field->type === 'select')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.select', ['field' => $field])
@elseif($field->type === 'widgetConfigFields')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.widgetConfigFields', ['field' => $field])
@elseif($field->type === 'email')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.email', ['field' => $field])
@elseif($field->type === 'phone')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.phone', ['field' => $field])
@elseif($field->type === 'gender')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.gender', ['field' => $field])
@elseif($field->type === 'birthday' || $field->type === 'date')
    {{-- 'date' is a plain alias of 'birthday' — same <input type=date>, nothing User-specific about the partial itself. --}}
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.birthday', ['field' => $field])
@elseif($field->type === 'password')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.password', ['field' => $field])
@elseif($field->type === 'wishlist_table')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.wishlist_table', ['field' => $field])
@elseif($field->type === 'addresses_table')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.addresses_table', ['field' => $field])
@elseif($field->type === 'cart_table')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.cart_table', ['field' => $field])
@elseif($field->type === 'video')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.video', ['field' => $field])
@elseif($field->type === 'attribute_options_field')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.attribute_options_field', ['field' => $field])
@elseif($field->type === 'relationManager')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.relationManager', ['field' => $field])
@elseif($field->type === 'custom_delivery_method')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.custom_delivery_method', ['field' => $field])
@elseif($field->type === 'custom_delivery_type')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.custom_delivery_type', ['field' => $field])
@elseif($field->type === 'custom_payment_method')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.custom_payment_method', ['field' => $field])
@elseif($field->type === 'custom_history')
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.custom_history', ['field' => $field])
@elseif(!empty($field->repeaterColumns))
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.repeater', ['field' => $field])
@elseif($field->type === 'view' && $field->userType)
    {{--
        #[Field(type: 'view', view: '...')] — inject an arbitrary Blade
        template, documented in Attributes/Field.php but previously
        unreachable here. AttributeSchemaReader::processFieldAttr() stores
        the configured view path in $field->userType (there's no separate
        $field->view — 'view' reuses the same slot the legacy
        FieldTypeRegistry pipeline uses for a custom type's Blade path).
        render() only passes module/moduleConfig/action (no live model), so
        resolve the record the same lazy way relation fields already do
        elsewhere in this component — $this is the ModuleForm instance in
        this scope, same as $this->isFieldVisible() a few lines up in
        module-form.blade.php.
    --}}
    @include($field->userType, [
        'model' => $this->id ? $moduleConfig->model::find($this->id) : new ($moduleConfig->model)(),
        'field' => $field,
        'module' => $module,
    ])
@else
    @include('nexus::' . config('nexus.template') . '.livewire.field_types.unsupported', ['field' => $field])
@endif
