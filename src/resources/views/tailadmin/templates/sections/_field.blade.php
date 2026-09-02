{{--
    Single field-type dispatch point, shared by base.blade.php, columns_2.blade.php
    and columns_3.blade.php so the resolution chain (module override -> registry
    -> built-in partial -> unknown) exists once per theme instead of duplicated
    per section-layout file.

    Wraps the rendered field in a data-nexus-field div ONLY when #[Field(showWhen:)]
    is set — for every field without showWhen (all 28+ existing modules, as of
    this partial's introduction) the wrapper is entirely absent, so the DOM stays
    byte-identical to before this partial existed. See Services/FieldVisibilityEvaluator.php
    for how the data-nexus-show-when JSON is evaluated (server) and
    nexus-conditional-fields.js for its client-side mirror.

    Expects: $field, $module. Optional: $model, $action, $tab_lang, $formData, $modelSchema.
--}}
@php
    $__nexusRegistry = app(\Nodex\Nexus\Services\FieldTypeRegistry::class);
    $__nexusRegisteredView = $__nexusRegistry->resolveViewName($field->type);
    $__nexusHasConditional = !empty($field->showWhen);
@endphp
@if($__nexusHasConditional)
<div class="nexus-conditional" data-nexus-field="{{ $field->name }}"
     data-nexus-show-when="{{ json_encode($field->showWhen) }}" data-nexus-logic="{{ $field->showWhenLogic }}"
     @if($field->clearWhenHidden) data-nexus-clear="1" @endif>
@endif
    @if (View::exists(Str::lcfirst($module->name).'::admin.field_types.' . $field->type))
        @include(Str::lcfirst($module->name).'::admin.field_types.' . $field->type, ['tab_lang' => $tab_lang ?? null])
    @elseif ($__nexusRegisteredView)
        @include($__nexusRegisteredView, ['tab_lang' => $tab_lang ?? null])
    @elseif ($__nexusRegistry->hasRenderer($field->type))
        {!! $__nexusRegistry->render($field->type, new \Nodex\Nexus\Dto\FieldRenderContext($field, $model ?? null, $module, $action ?? null, $tab_lang ?? null, $formData ?? [], $modelSchema ?? [])) !!}
    @elseif (View::exists('nexus::'. config('nexus.template').'.templates.field_types.' . $field->type))
        @include('nexus::'. config('nexus.template').'.templates.field_types.' . $field->type, ['tab_lang' => $tab_lang ?? null])
    @else
        @include('nexus::'. config('nexus.template').'.templates.field_types.unknown', ['tab_lang' => $tab_lang ?? null])
    @endif
@if($__nexusHasConditional)
</div>
@endif
