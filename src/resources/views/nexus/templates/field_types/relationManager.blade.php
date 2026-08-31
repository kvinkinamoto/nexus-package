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
    // $module here is the DefaultModuleConfigurationDto itself (see
    // EditActionMethod::handle()'s 'module' => $moduleConfig), not the
    // Eloquent Module row — no ->config indirection needed.
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
<div class="mb-3">
    @if(!empty($field->label))
        <label class="form-label">
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        </label>
    @endif

    @if(!$__nexusParentId)
        <div class="text-muted small">@lang('nexus::translate.relation_manager_available_after_save')</div>
    @elseif($__nexusRows->isEmpty())
        <div class="text-muted small">@lang('nexus::translate.no_related_records')</div>
    @else
        <ul class="list-group mb-2">
            @foreach($__nexusRows as $__nexusRow)
                <li class="list-group-item d-flex align-items-center justify-content-between">
                    <span>{{ $__nexusRow->{$__nexusShowField} ?? $__nexusRow->getKey() }}</span>
                    @if($__nexusRelatedModule)
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-secondary" target="_blank"
                               href="{{ route('nexus.module.action', ['module' => $__nexusRelatedModule, 'action' => 'edit', 'id' => $__nexusRow->getKey()]) }}">
                                @lang('nexus::translate.edit')
                            </a>
                            <form method="post" target="_blank"
                                  action="{{ route('nexus.module.action', ['module' => $__nexusRelatedModule, 'action' => 'delete', 'id' => $__nexusRow->getKey()]) }}"
                                  onsubmit="return confirm('{{ __('nexus::translate.are_u_sure') }}')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger">@lang('nexus::translate.delete')</button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if($__nexusParentId && $__nexusRelatedModule && $__nexusForeignKey)
        <a target="_blank" href="{{ route('nexus.module.action', ['module' => $__nexusRelatedModule, 'action' => 'index']) }}?{{ http_build_query(['dyn' => [['column' => $__nexusForeignKey, 'operator' => '=', 'value' => $__nexusParentId]]]) }}">
            @lang('nexus::translate.view_all') &rarr;
        </a>
    @endif
</div>
