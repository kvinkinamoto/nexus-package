@extends('nexus::' . config('nexus.template') . '.layouts.adminpanel')

@section('contentPageCaption')
    @lang($module->name . '::' . 'translate.' . $module->name)
@endsection

@section('mainContent')
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            @lang($module->name . '::' . 'translate.index_title')
        </h2>
    </div>

    @livewire('nexus-module-table', ['moduleName' => $module->name])
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
