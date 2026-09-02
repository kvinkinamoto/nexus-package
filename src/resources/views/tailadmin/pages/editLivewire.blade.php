@extends('nexus::' . config('nexus.template') . '.layouts.adminpanel')

@section('contentPageCaption')
    @lang($module->name . '::' . 'translate.' . $module->name)
@endsection

@section('mainContent')
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            @lang('nexus::translate.edit')
        </h2>
    </div>

    @livewire('nexus-module-form', ['moduleName' => $module->name, 'id' => $id])
@endsection
