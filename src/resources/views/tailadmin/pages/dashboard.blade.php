@extends('nexus::' . config('nexus.template') . '.layouts.adminpanel')

@section('contentPageCaption')
    @lang('nexus::translate.dashboard')
@endsection

@section('mainContent')
    <div x-data="{ customizeOpen: false }">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">@lang('nexus::translate.dashboard')</h2>
            <button type="button" @click="customizeOpen = true"
                class="inline-flex items-center gap-1.5 rounded-lg bg-warning-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-warning-600">
                <i class="bx bxs-dashboard"></i> @lang('nexus::translate.customize_dashboard')
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" id="dashboardCardsContainer">
            @forelse($cards as $card)
                <div data-widget-key="{{ $card['key'] }}">
                    {!! $card['html'] !!}
                </div>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-blue-light-200 bg-blue-light-50 p-4 text-sm text-blue-light-700 dark:border-blue-light-800 dark:bg-blue-light-500/10 dark:text-blue-light-300">
                    @lang('nexus::translate.no_widgets_on_dashboard')
                </div>
            @endforelse
        </div>

        <!-- Customize dashboard drawer -->
        <div class="fixed inset-0 z-99999" x-show="customizeOpen" x-cloak>
            <div class="absolute inset-0 bg-gray-900/50" @click="customizeOpen = false"
                x-show="customizeOpen" x-transition></div>
            <div class="absolute right-0 top-0 h-full w-full max-w-100 bg-white shadow-theme-xl dark:bg-gray-900"
                x-show="customizeOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h5 class="text-base font-semibold text-gray-800 dark:text-white/90">@lang('nexus::translate.available_widgets')</h5>
                    <button type="button" @click="customizeOpen = false" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="custom-scrollbar h-[calc(100%-64px)] overflow-y-auto p-4">
                    <ul class="flex flex-col gap-2" id="dashboardWidgetList">
                        @foreach($widgetKeys as $key)
                            @php($widget = $availableWidgets[$key] ?? null)
                            @continue(!$widget)
                            <li data-widget-key="{{ $key }}"
                                class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-800">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" class="widget-toggle h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" checked value="{{ $key }}">
                                    {{ $widget['meta']->label }}
                                </label>
                                <div class="flex gap-1">
                                    <button type="button" class="widget-move-up flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5" title="@lang('nexus::translate.move_up')"><i class="bx bx-up-arrow-alt text-sm"></i></button>
                                    <button type="button" class="widget-move-down flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5" title="@lang('nexus::translate.move_down')"><i class="bx bx-down-arrow-alt text-sm"></i></button>
                                </div>
                            </li>
                        @endforeach
                        @foreach($availableWidgets as $key => $widget)
                            @continue(in_array($key, $widgetKeys, true))
                            <li data-widget-key="{{ $key }}"
                                class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-800">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" class="widget-toggle h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" value="{{ $key }}">
                                    {{ $widget['meta']->label }}
                                </label>
                                <div class="flex gap-1">
                                    <button type="button" class="widget-move-up flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5" title="@lang('nexus::translate.move_up')"><i class="bx bx-up-arrow-alt text-sm"></i></button>
                                    <button type="button" class="widget-move-down flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5" title="@lang('nexus::translate.move_down')"><i class="bx bx-down-arrow-alt text-sm"></i></button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-nexus-widget-lazy]').forEach(function (placeholder) {
        const key = placeholder.dataset.nexusWidgetLazy;
        fetch('{{ url(config('nexus.admin_prefix') . '/widgets') }}/' + encodeURIComponent(key) + '/card', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => r.json())
            .then((data) => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = data.html || '';
                placeholder.replaceWith(...wrapper.childNodes);
            })
            .catch(() => {});
    });

    const list = document.getElementById('dashboardWidgetList');
    if (!list) return;

    function collectEnabledKeys() {
        return Array.from(list.querySelectorAll('li'))
            .filter((li) => li.querySelector('.widget-toggle').checked)
            .map((li) => li.dataset.widgetKey);
    }

    function save() {
        fetch("{{ route('nexus.preferences.dashboard-layout') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ widgets: collectEnabledKeys() }),
        })
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    alert("@lang('nexus::translate.error_saving_dashboard')");
                    return;
                }
                window.location.reload();
            })
            .catch(() => alert("@lang('nexus::translate.error_saving_dashboard')"));
    }

    list.addEventListener('change', function (e) {
        if (e.target.classList.contains('widget-toggle')) {
            save();
        }
    });

    list.addEventListener('click', function (e) {
        const upBtn = e.target.closest('.widget-move-up');
        const downBtn = e.target.closest('.widget-move-down');
        if (!upBtn && !downBtn) return;

        const li = e.target.closest('li');
        if (upBtn && li.previousElementSibling) {
            list.insertBefore(li, li.previousElementSibling);
            save();
        } else if (downBtn && li.nextElementSibling) {
            list.insertBefore(li.nextElementSibling, li);
            save();
        }
    });
});
</script>
@endpush
