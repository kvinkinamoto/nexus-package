@extends('nexus::' . config('nexus.template') . '.layouts.adminpanel')

@section('contentPageCaption')
    @lang('nexus::translate.dashboard')
@endsection

@section('mainContent')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">@lang('nexus::translate.dashboard')</h4>
                <button class="btn btn-warning btn-rounded waves-effect waves-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardCustomizeOffcanvas" aria-controls="dashboardCustomizeOffcanvas">
                    <i class="mdi mdi-view-dashboard-edit-outline me-1"></i> @lang('nexus::translate.customize_dashboard')
                </button>
            </div>
        </div>
    </div>

    <div class="row" id="dashboardCardsContainer">
        @forelse($cards as $card)
            <div class="col-xl-4 col-md-6" data-widget-key="{{ $card['key'] }}">
                {!! $card['html'] !!}
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">@lang('nexus::translate.no_widgets_on_dashboard')</div>
            </div>
        @endforelse
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="dashboardCustomizeOffcanvas" aria-labelledby="dashboardCustomizeOffcanvasLabel" style="width: 400px;">
        <div class="offcanvas-header border-bottom">
            <h5 id="dashboardCustomizeOffcanvasLabel">@lang('nexus::translate.available_widgets')</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-group" id="dashboardWidgetList">
                @foreach($widgetKeys as $key)
                    @php($widget = $availableWidgets[$key] ?? null)
                    @continue(!$widget)
                    <li class="list-group-item d-flex align-items-center justify-content-between" data-widget-key="{{ $key }}">
                        <div class="form-check">
                            <input class="form-check-input widget-toggle" type="checkbox" checked value="{{ $key }}" id="widget-toggle-{{ $key }}">
                            <label class="form-check-label" for="widget-toggle-{{ $key }}">{{ $widget['meta']->label }}</label>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary widget-move-up" title="@lang('nexus::translate.move_up')"><i class="mdi mdi-arrow-up"></i></button>
                            <button type="button" class="btn btn-outline-secondary widget-move-down" title="@lang('nexus::translate.move_down')"><i class="mdi mdi-arrow-down"></i></button>
                        </div>
                    </li>
                @endforeach
                @foreach($availableWidgets as $key => $widget)
                    @continue(in_array($key, $widgetKeys, true))
                    <li class="list-group-item d-flex align-items-center justify-content-between" data-widget-key="{{ $key }}">
                        <div class="form-check">
                            <input class="form-check-input widget-toggle" type="checkbox" value="{{ $key }}" id="widget-toggle-{{ $key }}">
                            <label class="form-check-label" for="widget-toggle-{{ $key }}">{{ $widget['meta']->label }}</label>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary widget-move-up" title="@lang('nexus::translate.move_up')"><i class="mdi mdi-arrow-up"></i></button>
                            <button type="button" class="btn btn-outline-secondary widget-move-down" title="@lang('nexus::translate.move_down')"><i class="mdi mdi-arrow-down"></i></button>
                        </div>
                    </li>
                @endforeach
            </ul>
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
