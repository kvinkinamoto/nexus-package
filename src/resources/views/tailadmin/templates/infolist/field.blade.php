{{--
    Read-only value renderer for a single field on the Infolist (view) screen.
    Mirrors templates/sections/_field.blade.php's / livewire/field_types/
    dispatch.blade.php's resolution order one tier further:
      1. {module}::admin.infolist_types.{type} — module override, full replace
      2. FieldTypeRegistry::resolveViewName()/hasRenderer() — so a type
         registered via a module's FieldTypes/ folder or the plugin hook
         gets a read-only rendering too, not just an edit-form one
      3. the @switch below — one built-in fallback here instead of 28
         separate field_types/*.blade.php partials, since those are
         edit-form inputs (validation, Choices.js, Dropzone, ...) with no
         read-only version; an Infolist value just needs to be formatted,
         not collected
      4. the @switch's @default — raw value / "no value"

    Expects: $field, $module, $model, $formData. Optional: $tab_lang.
--}}
@php
    $__nexusInfolistOverride = Str::lcfirst($module->name) . '::admin.infolist_types.' . $field->type;
    $__nexusInfolistRegistry = app(\Nodex\Nexus\Services\FieldTypeRegistry::class);
    $__nexusInfolistType = $__nexusInfolistRegistry->resolveType($field->type);
    $__nexusInfolistRegisteredView = $__nexusInfolistRegistry->resolveViewName($__nexusInfolistType);
@endphp
@if (View::exists($__nexusInfolistOverride))
    @include($__nexusInfolistOverride, ['tab_lang' => $tab_lang ?? null])
@elseif ($__nexusInfolistRegisteredView)
    @include($__nexusInfolistRegisteredView, ['tab_lang' => $tab_lang ?? null])
@elseif ($__nexusInfolistRegistry->hasRenderer($__nexusInfolistType))
    {!! $__nexusInfolistRegistry->render($__nexusInfolistType, new \Nodex\Nexus\Dto\FieldRenderContext($field, $model ?? null, $module, $action ?? null, $tab_lang ?? null, $formData ?? [], $modelSchema ?? [])) !!}
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
    <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">
            {{ nexus_trans_label($module->name, $field->label ?? null, $field->name) }}
            @if(isset($tab_lang))
                <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ strtoupper($tab_lang) }}</span>
            @endif
        </dt>
        <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
            @switch($field->type)
                @case('password')
                    <span class="text-gray-400">••••••••</span>
                    @break

                @case('boolean')
                    @if($value)
                        <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-success-700 dark:bg-success-500/15 dark:text-success-400"><i class="{{ nexus_icon('success') }}"></i></span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-gray-500 dark:bg-white/5 dark:text-gray-400"><i class="{{ nexus_icon('block') }}"></i></span>
                    @endif
                    @break

                @case('image')
                    @php
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
                        <a href="/{{ $__nexusImagePath }}" target="_blank" rel="noopener">
                            <img src="/{{ $__nexusImagePath }}"
                                class="h-30 w-30 rounded-lg border border-gray-200 object-contain dark:border-gray-800" alt="">
                        </a>
                    @else
                        <span class="text-gray-400">@lang('nexus::translate.no_value')</span>
                    @endif
                    @break

                @case('images')
                @case('imagesAlign')
                    @php $__nexusImages = is_iterable($value) ? collect($value) : collect(); @endphp
                    @if($__nexusImages->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($__nexusImages as $__nexusImg)
                                @php $__nexusImgPath = is_string($__nexusImg) ? $__nexusImg : ($__nexusImg->path ?? null); @endphp
                                @if($__nexusImgPath)
                                    <a href="/{{ $__nexusImgPath }}" target="_blank" rel="noopener">
                                        <img src="/{{ $__nexusImgPath }}" class="h-20 w-20 rounded-lg border border-gray-200 object-contain dark:border-gray-800" alt="">
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <span class="text-gray-400">@lang('nexus::translate.no_value')</span>
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
                        $__nexusEnumCase = null;
                        foreach (($field->enum ? $field->enum::cases() : []) as $__nexusCase) {
                            if ($__nexusCase->value == $value) {
                                $__nexusEnumCase = $__nexusCase;
                                break;
                            }
                        }
                        $__nexusEnumLabel = $__nexusEnumCase ? nexus_enum_display($__nexusEnumCase, $module->name ?? 'nexus') : null;
                    @endphp
                    <span>{{ $__nexusEnumLabel ?: ($value !== null && $value !== '' ? $value : __('nexus::translate.no_value')) }}</span>
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
                            <span class="text-gray-400">@lang('nexus::translate.no_value')</span>
                        @endif
                    @elseif($__nexusRelValue)
                        {{ $__nexusRelValue->{$__nexusShowField} ?? $__nexusRelValue->getKey() }}
                    @else
                        <span class="text-gray-400">@lang('nexus::translate.no_value')</span>
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
                        <span class="text-gray-400">{{ count($value) }} @lang('nexus::translate.view_all')</span>
                    @elseif(is_object($value) && !($value instanceof \Stringable) && !method_exists($value, '__toString'))
                        <span class="text-gray-400">@lang('nexus::translate.no_value')</span>
                    @else
                        <span>{{ $value !== null && $value !== '' ? $value : __('nexus::translate.no_value') }}</span>
                    @endif
            @endswitch
        </dd>
    </div>
@endif
