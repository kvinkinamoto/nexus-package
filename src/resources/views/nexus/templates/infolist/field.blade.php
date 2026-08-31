{{--
    Read-only value renderer for a single field on the Infolist (view) screen.
    Mirrors templates/sections/_field.blade.php's override-first resolution
    (module can supply {module}::admin.infolist_types.{type} to fully replace
    a type's display), but the built-in fallback is one @switch here instead
    of 28 separate field_types/*.blade.php partials — those are edit-form
    inputs (validation, Choices.js, Dropzone, ...) with no read-only version;
    an Infolist value just needs to be formatted, not collected.

    Expects: $field, $module, $model, $formData. Optional: $tab_lang.
--}}
@php
    $__nexusInfolistOverride = Str::lcfirst($module->name) . '::admin.infolist_types.' . $field->type;
@endphp
@if (View::exists($__nexusInfolistOverride))
    @include($__nexusInfolistOverride, ['tab_lang' => $tab_lang ?? null])
@else
    @php
        // A field's relation method can be broken (e.g. missing a return
        // statement — see ShopCategory::mediaImages()) without that being
        // this screen's problem to surface as a 500; degrade to "no value"
        // instead of taking the whole page down.
        try {
            if (isset($tab_lang)) {
                $value = isset($model) ? $model->getTranslation($field->name, $tab_lang, false) : null;
            } else {
                $value = isset($model) ? ($model->{$field->name} ?? null) : null;
            }
        } catch (\Throwable $e) {
            $value = null;
        }
        if ($value === null || $value === '') {
            $value = $field->default ?? null;
        }
        if ($value instanceof \UnitEnum) {
            $value = $value->value ?? $value->name;
        }
    @endphp
    <dt class="col-sm-3 text-muted fw-normal">
        @if(isset($field->label) && !empty($field->label))
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        @else
            @if(str_contains($field->name, '::'))
                @lang($field->name)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->name)
            @endif
        @endif
        @if(isset($tab_lang))
            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ strtoupper($tab_lang) }}</span>
        @endif
    </dt>
    <dd class="col-sm-9 mb-2">
        @switch($field->type)
            @case('password')
                <span class="text-muted">••••••••</span>
                @break

            @case('boolean')
                @if($value)
                    <span class="badge bg-success-subtle text-success-emphasis"><i class="{{ nexus_icon('success') }} align-middle"></i></span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="{{ nexus_icon('block') }} align-middle"></i></span>
                @endif
                @break

            @case('image')
                @php
                    // $value can resolve to an empty Collection instead of a string/null
                    // for some modules' image relations — Collection::__get() throws
                    // rather than returning null for an undefined property, so `??`
                    // alone can't guard against it; only treat genuinely path-bearing
                    // shapes as an image, everything else is "no value".
                    try {
                        $__nexusImagePath = match(true) {
                            is_string($value) && $value !== '' => $value,
                            is_array($value) && !empty($value['path']) => $value['path'],
                            is_object($value) && !($value instanceof \Illuminate\Support\Collection) && !empty($value->path ?? null) => $value->path,
                            default => null,
                        };
                    } catch (\Throwable $e) {
                        $__nexusImagePath = null;
                    }
                @endphp
                @if($__nexusImagePath)
                    <a href="/{{ $__nexusImagePath }}" class="image-popup" target="_blank">
                        <img src="/{{ $__nexusImagePath }}"
                             class="img-thumbnail" style="max-width: 120px; max-height: 120px; object-fit: contain;" alt="">
                    </a>
                @else
                    <span class="text-muted">@lang('nexus::translate.no_value')</span>
                @endif
                @break

            @case('images')
            @case('imagesAlign')
                @php $__nexusImages = is_iterable($value) ? collect($value) : collect(); @endphp
                @if($__nexusImages->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($__nexusImages as $__nexusImg)
                            @php $__nexusImgPath = is_string($__nexusImg) ? $__nexusImg : ($__nexusImg->path ?? null); @endphp
                            @if($__nexusImgPath)
                                <a href="/{{ $__nexusImgPath }}" class="image-popup" target="_blank">
                                    <img src="/{{ $__nexusImgPath }}" class="img-thumbnail" style="max-width: 80px; max-height: 80px; object-fit: contain;" alt="">
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <span class="text-muted">@lang('nexus::translate.no_value')</span>
                @endif
                @break

            @case('date')
                <span>{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y') : __('nexus::translate.no_value') }}</span>
                @break

            @case('datetime')
                <span>{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y H:i') : __('nexus::translate.no_value') }}</span>
                @break

            @case('enum')
                @php
                    $__nexusEnumLabel = null;
                    foreach (($field->enum ? $field->enum::cases() : []) as $__nexusCase) {
                        if ($__nexusCase->value == $value) {
                            $__nexusEnumLabel = $__nexusCase->value;
                            break;
                        }
                    }
                @endphp
                <span>{{ $__nexusEnumLabel ? __(($module->name ?? 'nexus') . '::translate.' . $__nexusEnumLabel) : ($value ?: __('nexus::translate.no_value')) }}</span>
                @break

            @case('select')
                @php
                    $__nexusSelectLabel = null;
                    foreach (($field->customData ?? []) as $__nexusOpt) {
                        if (($__nexusOpt['value'] ?? null) == $value) {
                            $__nexusSelectLabel = $__nexusOpt['name'] ?? null;
                            break;
                        }
                    }
                @endphp
                <span>{{ $__nexusSelectLabel ?? ($value !== null && $value !== '' ? $value : __('nexus::translate.no_value')) }}</span>
                @break

            @case('relation')
                @php
                    $__nexusRelConfig = $module->relations->is_available[$field->name] ?? null;
                    $__nexusShowField = $__nexusRelConfig->showField ?? 'name';
                    $__nexusRelValue = (isset($model) && method_exists($model, $field->name)) ? $model->{$field->name} : null;
                @endphp
                @if($__nexusRelValue instanceof \Illuminate\Support\Collection || $__nexusRelValue instanceof \Illuminate\Database\Eloquent\Collection)
                    @if($__nexusRelValue->isNotEmpty())
                        {{ $__nexusRelValue->map(fn($__r) => $__r->{$__nexusShowField} ?? $__r->getKey())->implode(', ') }}
                    @else
                        <span class="text-muted">@lang('nexus::translate.no_value')</span>
                    @endif
                @elseif($__nexusRelValue)
                    {{ $__nexusRelValue->{$__nexusShowField} ?? $__nexusRelValue->getKey() }}
                @else
                    <span class="text-muted">@lang('nexus::translate.no_value')</span>
                @endif
                @break

            @case('relationManager')
                @include('nexus::'. config('nexus.template').'.templates.field_types.relationManager', ['field' => $field, 'module' => $module, 'model' => $model ?? null])
                @break

            @case('view')
                @includeIf($field->userType, ['field' => $field, 'module' => $module, 'model' => $model ?? null, 'formData' => $formData ?? []])
                @break

            @default
                @if(is_array($value) || $value instanceof \Illuminate\Support\Collection)
                    <span class="text-muted">{{ count($value) }} @lang('nexus::translate.view_all')</span>
                @elseif(is_object($value) && !($value instanceof \Stringable) && !method_exists($value, '__toString'))
                    {{-- An unrecognized type's value resolved to some other object
                         (e.g. a Model with no __toString) — show nothing rather
                         than let Blade's implicit string cast throw. --}}
                    <span class="text-muted">@lang('nexus::translate.no_value')</span>
                @else
                    <span>{{ $value !== null && $value !== '' ? $value : __('nexus::translate.no_value') }}</span>
                @endif
        @endswitch
    </dd>
@endif
