{{-- Livewire Етап 6 — port of Order's field_types/custom_history.blade.php. Read-only, model-driven like the User *_table partials. --}}
@php
    $model = $this->id ? $moduleConfig->model::find($this->id) : null;
    $entries = $model ? $model->history()->with('status')->orderBy('id', 'desc')->get() : collect();
    $tz = config('app.timezone');
@endphp
<div class="mb-4">
    @if(!empty($field->label))
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])
    @endif

    @if($entries->isEmpty())
        <p class="py-4 text-sm text-gray-400">@lang('order::translate.no_history')</p>
    @else
        <ul class="relative m-0 list-none border-l-2 border-gray-200 pl-6 dark:border-gray-800">
            @foreach($entries as $entry)
                <li class="relative py-2">
                    <span class="absolute -left-[calc(1.5rem+5px)] top-2.5 h-3 w-3 rounded-full border-2 border-white bg-brand-500 dark:border-gray-900"></span>
                    <div class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $entry->status?->name ?? '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $entry->created_at?->timezone($tz)->format('d.m.Y H:i') }}</div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
