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
<div class="mb-4">
    @if(!empty($field->label))
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])
    @endif

    @if(!$model)
        <div class="text-sm text-gray-400">@lang('nexus::translate.relation_manager_available_after_save')</div>
    @elseif($rows->isEmpty())
        <div class="text-sm text-gray-400">@lang('nexus::translate.no_related_records')</div>
    @else
        <ul class="mb-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-white/5 dark:border-gray-800">
            @foreach($rows as $row)
                <li class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                    <span class="text-gray-700 dark:text-gray-300">{{ $row->{$showField} ?? $row->getKey() }}</span>
                    @if($relatedModule)
                        <div class="flex gap-1.5">
                            <a target="_blank"
                               href="{{ route('nexus.module.action', ['module' => $relatedModule, 'action' => 'edit', 'id' => $row->getKey()]) }}"
                               class="rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                                @lang('nexus::translate.edit')
                            </a>
                            <form method="post" target="_blank"
                                  action="{{ route('nexus.module.action', ['module' => $relatedModule, 'action' => 'delete', 'id' => $row->getKey()]) }}"
                                  onsubmit="return confirm('{{ __('nexus::translate.are_u_sure') }}')">
                                @csrf
                                <button type="submit" class="rounded-md border border-error-200 px-2 py-1 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
                                    @lang('nexus::translate.delete')
                                </button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if($model && $relatedModule && $foreignKey)
        <a target="_blank" class="text-sm font-medium text-brand-500 hover:text-brand-600"
           href="{{ route('nexus.module.action', ['module' => $relatedModule, 'action' => 'index']) }}?{{ http_build_query(['dyn' => [['column' => $foreignKey, 'operator' => '=', 'value' => $model->getKey()]]]) }}">
            @lang('nexus::translate.view_all') &rarr;
        </a>
    @endif
</div>
