@extends('nexus::'. config('nexus.template').'.layouts.adminpanel')
@section('head')

@stop
@section('searchFormAction')
    #
@stop

@section('contentPageCaption')
    @lang(lcfirst($module->name).'::translate.'.lcfirst($module->name)) @lang('nexus::translate.window_view')
@stop

@section('contentPageNavButtonPanel')
    @parent
    <div class="mb-4 flex justify-end gap-2">
        <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'edit', 'id' => $model->id]) }}"
            id="go_to_edit"
            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
            <i class="{{ nexus_icon('edit') }}"></i>
            @lang('nexus::translate.edit')
        </a>
        <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'index']) }}" id="go_to_index"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
            @lang('nexus::translate.Close')
        </a>
    </div>
@stop

@section('contentPageBreadcrumbItem') @stop

@section('mainContent')

    @if(!empty($module->tabs))
        <div x-data="{ activeTab: '{{ $module->tabs[array_key_first($module->tabs)]->name ?? '' }}' }">
            <div class="mb-4 flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-800">
                @foreach($module->tabs as $tab)
                    <button type="button" @click="activeTab = '{{ $tab->name }}'"
                        class="border-b-2 px-3 py-2 text-sm font-medium"
                        :class="activeTab === '{{ $tab->name }}' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                        @lang($module->name . '::' . 'translate.' . $tab->label)
                    </button>
                @endforeach
            </div>

            @foreach($module->tabs as $tab)
                <div x-show="activeTab === '{{ $tab->name }}'" x-cloak>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach($module->sectionColumns as $column)
                            @if($column->tab == $tab->name)
                                <div class="col_name_{{ $column->name }} {{ $column->class }}">
                                    @include('nexus::'. config('nexus.template').'.templates.infolistSection', ['column' => $column])
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($module->sectionColumns as $column)
                <div class="col_name_{{ $column->name }} {{ $column->class }}">
                    @include('nexus::'. config('nexus.template').'.templates.infolistSection', ['column' => $column])
                </div>
            @endforeach
        </div>
    @endif

    @include('nexus::'. config('nexus.template').'.templates.infolistSection', ['column' => null])

    @yield('contentPageNavButtonPanel')

@stop
