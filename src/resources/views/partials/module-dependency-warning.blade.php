@if(!empty($moduleMissingDependencies ?? []))
    <div class="container-fluid pt-3">
        <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
            <i class="mdi mdi-alert-circle-outline fs-18 me-2"></i>
            <div>
                <strong>{{ __('nexus::translate.module_missing_dependencies_title') }}</strong>
                {{ __('nexus::translate.module_missing_dependencies_body', ['modules' => implode(', ', $moduleMissingDependencies)]) }}
            </div>
        </div>
    </div>
@endif
