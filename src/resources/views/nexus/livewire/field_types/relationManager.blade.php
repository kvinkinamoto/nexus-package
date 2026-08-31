{{--
    Livewire Етап 6 — port of templates/field_types/relationManager.blade.php.
    Read-only, model-driven like the User *_table partials (see
    wishlist_table.blade.php's docblock) — fetches $model directly via
    $this->id rather than through $this->data, since this field type never
    had any editable state to begin with.
--}}
@php
    $model = $this->id ? $moduleConfig->model::find($this->id) : null;
    $relConfig = $moduleConfig->relations->is_available[$field->name] ?? null;
    $relatedModule = $relConfig->relatedModule ?? null;
    $showField = $relConfig->showField ?? 'id';
    $rows = collect();
    $foreignKey = null;

    if ($model && method_exists($model, $field->name)) {
        $relation = $model->{$field->name}();
        if (method_exists($relation, 'getForeignKeyName')) {
            $foreignKey = $relation->getForeignKeyName();
        }
        $rows = $relation->latest()->take(5)->get();
    }
@endphp
<div class="mb-3">
    @if(!empty($field->label))
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])
    @endif

    @if(!$model)
        <div class="text-muted small">@lang('nexus::translate.relation_manager_available_after_save')</div>
    @elseif($rows->isEmpty())
        <div class="text-muted small">@lang('nexus::translate.no_related_records')</div>
    @else
        <ul class="list-group mb-2">
            @foreach($rows as $row)
                <li class="list-group-item d-flex align-items-center justify-content-between">
                    <span>{{ $row->{$showField} ?? $row->getKey() }}</span>
                    @if($relatedModule)
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-secondary" target="_blank"
                               href="{{ route('nexus.module.action', ['module' => $relatedModule, 'action' => 'edit', 'id' => $row->getKey()]) }}">
                                @lang('nexus::translate.edit')
                            </a>
                            <form method="post" target="_blank"
                                  action="{{ route('nexus.module.action', ['module' => $relatedModule, 'action' => 'delete', 'id' => $row->getKey()]) }}"
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

    @if($model && $relatedModule && $foreignKey)
        <a target="_blank" href="{{ route('nexus.module.action', ['module' => $relatedModule, 'action' => 'index']) }}?{{ http_build_query(['dyn' => [['column' => $foreignKey, 'operator' => '=', 'value' => $model->getKey()]]]) }}">
            @lang('nexus::translate.view_all') &rarr;
        </a>
    @endif
</div>
