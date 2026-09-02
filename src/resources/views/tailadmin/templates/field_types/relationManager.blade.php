{{--
    Read-only relation manager — shows the most recent related rows with
    links to the related module's OWN edit/delete actions (not inline
    editing, unlike field_types/repeater.blade.php). "View all" links to that
    module's index, pre-filtered to this parent record via the existing
    UniversalFilterBuilder "dyn" query format — no new filtering mechanism
    needed. Requires #[Relation(relatedModule:)]; the foreign key is derived
    from the Eloquent relation itself, not declared separately.
--}}
@php
    $__nexusRelConfig = $module->relations->is_available[$field->name] ?? null;
    $__nexusRelatedModule = $__nexusRelConfig->relatedModule ?? null;
    $__nexusShowField = $__nexusRelConfig->showField ?? 'id';
    $__nexusRows = collect();
    $__nexusForeignKey = null;
    $__nexusParentId = (isset($model) && $model) ? $model->getKey() : null;

    if ($__nexusParentId && method_exists($model, $field->name)) {
        $__nexusRelation = $model->{$field->name}();
        if (method_exists($__nexusRelation, 'getForeignKeyName')) {
            $__nexusForeignKey = $__nexusRelation->getForeignKeyName();
        }
        $__nexusRows = $__nexusRelation->latest()->take(5)->get();
    }
@endphp
<div class="mb-4">
    @if(!empty($field->label))
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        </label>
    @endif

    @if(!$__nexusParentId)
        <div class="text-sm text-gray-400">@lang('nexus::translate.relation_manager_available_after_save')</div>
    @elseif($__nexusRows->isEmpty())
        <div class="text-sm text-gray-400">@lang('nexus::translate.no_related_records')</div>
    @else
        <ul class="mb-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-white/5 dark:border-gray-800">
            @foreach($__nexusRows as $__nexusRow)
                <li class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                    <span class="text-gray-700 dark:text-gray-300">{{ $__nexusRow->{$__nexusShowField} ?? $__nexusRow->getKey() }}</span>
                    @if($__nexusRelatedModule)
                        <div class="flex gap-1.5">
                            <a class="rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5" target="_blank"
                               href="{{ route('nexus.module.action', ['module' => $__nexusRelatedModule, 'action' => 'edit', 'id' => $__nexusRow->getKey()]) }}">
                                @lang('nexus::translate.edit')
                            </a>
                            <form method="post" target="_blank"
                                  action="{{ route('nexus.module.action', ['module' => $__nexusRelatedModule, 'action' => 'delete', 'id' => $__nexusRow->getKey()]) }}"
                                  onsubmit="return confirm('{{ __('nexus::translate.are_u_sure') }}')">
                                @csrf
                                <button type="submit" class="rounded-md border border-error-200 px-2 py-1 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">@lang('nexus::translate.delete')</button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if($__nexusParentId && $__nexusRelatedModule && $__nexusForeignKey)
        <a target="_blank" class="text-sm font-medium text-brand-500 hover:text-brand-600" href="{{ route('nexus.module.action', ['module' => $__nexusRelatedModule, 'action' => 'index']) }}?{{ http_build_query(['dyn' => [['column' => $__nexusForeignKey, 'operator' => '=', 'value' => $__nexusParentId]]]) }}">
            @lang('nexus::translate.view_all') &rarr;
        </a>
    @endif
</div>
