{{--
    Single field's type dispatch, factored out of module-form.blade.php so
    both the flat (non-wizard) loop and the per-step wizard loop share one
    copy. Expects $field (FieldConfigDto) in scope.

    Resolution order mirrors the legacy (non-Livewire) pipeline's
    templates/sections/_field.blade.php exactly, so FieldTypeRegistry is one
    shared source of truth across both themes instead of two — a type a
    module registers via its FieldTypes/ folder, or a plugin registers via
    the 'nexus.field_types.register' hook (see Services/FieldTypeRegistry.php),
    is now reachable here too, not just from the legacy pipeline:
      1. {module}::admin.livewire_field_types.{type} — module override/custom-type hook
      2. FieldTypeRegistry::resolveViewName()          — registerView() entries
      3. FieldTypeRegistry::hasRenderer()/render()      — registerClass()/registerCallback() entries
      4. livewire/field_types/{type}.blade.php by convention — every built-in
         type (string, text, number, ...) lives at exactly this path, so
         nothing needs registering here just for the type to exist; only the
         two spellings whose file doesn't match their type string ('editor'
         -> text.blade.php, 'date' -> birthday.blade.php) are registered as
         aliases, in NexusServiceProvider::registerBuiltInFieldTypeAliases().
      5. unsupported.blade.php — nothing above matched
--}}
@php
    $moduleNamespace = \Illuminate\Support\Str::lcfirst($module->name);
@endphp
@if(View::exists($moduleNamespace . '::admin.livewire_field_types.' . $field->type))
    {{--
        Module-scoped override/custom-type hook, same "module override wins"
        convention already used for repeater cells (field_types/repeater.blade.php).
        A module can drop {module}::admin.livewire_field_types.{type}.blade.php
        to either add a brand new type (nothing below matches it) or override
        a built-in one (e.g. 'phone') for that module only — every other
        module has no such view, so View::exists() is false for them and
        behavior is unchanged. Checked before the type is resolved through
        FieldTypeRegistry/aliases at all, so a module override always wins
        even over a globally-registered custom type.
    --}}
    @include($moduleNamespace . '::admin.livewire_field_types.' . $field->type, ['field' => $field])
@else
    @php
        $__nexusRegistry = app(\Nodex\Nexus\Services\FieldTypeRegistry::class);
        $__nexusType = $__nexusRegistry->resolveType($field->type);

        /*
         * A field of type image/images/videos that also carries #[Relation]
         * is a not-yet-supported relation-backed case — ModuleForm::mount()
         * already routes its value through the relation branches instead of
         * a plain string/array, so none of the plain-value renderers below
         * (registered or built-in) may be used for it; it must fall through
         * toward the structural checks below and, ultimately, the
         * unsupported banner, rather than crashing on a non-string $value.
         */
        $__nexusRelationBlocked = in_array($__nexusType, ['image', 'images', 'videos'], true)
            && !empty($moduleConfig->relations->is_available[$field->name] ?? null);

        $__nexusRegisteredView = $__nexusRelationBlocked ? null : $__nexusRegistry->resolveViewName($__nexusType);
        $__nexusHasRenderer = !$__nexusRelationBlocked && $__nexusRegistry->hasRenderer($__nexusType);
        $__nexusBuiltInView = 'nexus::' . config('nexus.template') . '.livewire.field_types.' . $__nexusType;
    @endphp
    @if($__nexusRegisteredView)
        @include($__nexusRegisteredView, ['field' => $field])
    @elseif($__nexusHasRenderer)
        {{--
            registerClass()/registerCallback() entries render via an explicit
            FieldRenderContext rather than inheriting this Blade scope — same
            contract the legacy pipeline's _field.blade.php already uses for
            this step, so a FieldTypeRenderer implementation works unchanged
            regardless of which theme/pipeline it ends up rendering under.
        --}}
        {!! $__nexusRegistry->render($__nexusType, new \Nodex\Nexus\Dto\FieldRenderContext($field, $this->id ? $moduleConfig->model::find($this->id) : new ($moduleConfig->model)(), $module, $action ?? null, null, $this->data ?? [], [])) !!}
    @elseif(!$__nexusRelationBlocked && View::exists($__nexusBuiltInView))
        @include($__nexusBuiltInView, ['field' => $field])
    @elseif(!empty($field->repeaterColumns))
        @include('nexus::' . config('nexus.template') . '.livewire.field_types.repeater', ['field' => $field])
    @elseif($field->type === 'view' && $field->userType)
        {{--
            #[Field(type: 'view', view: '...')] — inject an arbitrary Blade
            template. AttributeSchemaReader::processFieldAttr() stores the
            configured view path in $field->userType (there's no separate
            $field->view property) — this is a per-FIELD path rather than a
            per-TYPE one, so it stays its own branch instead of going through
            the registry.
        --}}
        @include($field->userType, [
            'model' => $this->id ? $moduleConfig->model::find($this->id) : new ($moduleConfig->model)(),
            'field' => $field,
            'module' => $module,
        ])
    @else
        @include('nexus::' . config('nexus.template') . '.livewire.field_types.unsupported', ['field' => $field])
    @endif
@endif
