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
    <div class="p-3 bg-light mb-3 rounded">
        <div class="row justify-content-end g-2">
            <div class="col-lg-2">
                <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'edit', 'id' => $model->id]) }}"
                   class="btn btn-primary w-100" id="go_to_edit">
                    <i class="{{ nexus_icon('edit') }} font-size-16 align-middle me-2"></i>
                    @lang('nexus::translate.edit')
                </a>
            </div>
            <div class="col-lg-2">
                <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'index']) }}"
                   class="btn btn-outline-secondary w-100" id="go_to_index">@lang('nexus::translate.Close')</a>
            </div>
        </div>
    </div>
@stop

@section('contentPageBreadcrumbItem') @stop

@section('mainContent')

    @if(!empty($module->tabs))
        <ul class="nav nav-tabs nav-justified">
            @foreach($module->tabs as $tab)
                <li class="nav-item">
                    <a href="#tab_{{ $tab->name }}"
                       data-bs-toggle="tab"
                       aria-expanded="false"
                       class="nav-link {{ $loop->first ? 'is_active' : '' }}">
                        <span class="d-block d-sm-none"><i class="{{ nexus_icon('home') }}"></i></span>
                        <span class="d-none d-sm-block">
                            @lang($module->name . '::' . 'translate.' . $tab->label)
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content pt-2 text-muted">

            @foreach($module->tabs as $tab)
                <div class="tab-pane {{ $loop->first ? 'show active' : '' }}" id="tab_{{ $tab->name }}">
                    <div class="row">
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
        @foreach($module->sectionColumns as $column)
            <div class="col_name_{{ $column->name }} {{ $column->class }}">
                @include('nexus::'. config('nexus.template').'.templates.infolistSection', ['column' => $column])
            </div>
        @endforeach
    @endif

    @include('nexus::'. config('nexus.template').'.templates.infolistSection', ['column' => null])

    @yield('contentPageNavButtonPanel')

@stop
