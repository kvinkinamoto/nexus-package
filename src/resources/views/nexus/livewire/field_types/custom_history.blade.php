{{-- Livewire Етап 6 — port of Order's field_types/custom_history.blade.php. Read-only, model-driven like the User *_table partials. --}}
@php
    $model = $this->id ? $moduleConfig->model::find($this->id) : null;
    $entries = $model ? $model->history()->with('status')->orderBy('id', 'desc')->get() : collect();
    $tz = config('app.timezone');
@endphp
<div class="mb-3">
    @if(!empty($field->label))
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])
    @endif

    <style>
        .order-history-timeline {
            position: relative;
            padding-left: 2rem;
            margin: 0;
            list-style: none;
        }
        .order-history-timeline::before {
            content: '';
            position: absolute;
            left: .75rem;
            top: .25rem;
            bottom: .25rem;
            width: 2px;
            background: var(--bs-border-color, #e9ebec);
        }
        .order-history-timeline .entry {
            position: relative;
            padding: .5rem 0 .5rem 1rem;
        }
        .order-history-timeline .entry::before {
            content: '';
            position: absolute;
            left: -1.65rem;
            top: 1rem;
            width: .75rem;
            height: .75rem;
            border-radius: 50%;
            background: var(--bs-primary, #405189);
            border: 2px solid var(--bs-body-bg, #fff);
        }
        .order-history-timeline .entry-status { font-weight: 600; }
        .order-history-timeline .entry-time { color: var(--bs-secondary-color, #98a6ad); font-size: .85em; }
        .order-history-empty { color: var(--bs-secondary-color, #98a6ad); padding: 1rem 0; }
    </style>

    @if($entries->isEmpty())
        <p class="order-history-empty">@lang('order::translate.no_history')</p>
    @else
        <ul class="order-history-timeline">
            @foreach($entries as $entry)
                <li class="entry">
                    <div class="entry-status">{{ $entry->status?->name ?? '—' }}</div>
                    <div class="entry-time">{{ $entry->created_at?->timezone($tz)->format('d.m.Y H:i') }}</div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
