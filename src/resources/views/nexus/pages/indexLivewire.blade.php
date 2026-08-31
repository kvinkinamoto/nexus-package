@extends('nexus::' . config('nexus.template') . '.layouts.adminpanel')

@section('contentPageCaption')
    @lang($module->name . '::' . 'translate.' . $module->name)
@endsection

@section('mainContent')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    @lang($module->name . '::' . 'translate.index_title')
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @livewire('nexus-module-table', ['moduleName' => $module->name])
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        function sendFormConfirm(shouldConfirm, action, message) {
            if (!shouldConfirm) {
                return true;
            }
            return confirm(message || ("Are you sure you want to " + action + "?"));
        }
    </script>
@stop
