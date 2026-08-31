{{--
    View Slot field type — renders an arbitrary Blade template inside the admin form.

    Available variables in the included view:
        $model   — the Eloquent model being edited (or null when creating)
        $field   — the FieldConfigDto instance (contains name, label, userType/view path, etc.)
        $module  — the Module model
        $formData — the full form data array

    Usage in ModuleConfiguration (or via #[Field] Attribute):
        $form->field('price_preview')
             ->view('shopProduct::admin.components.price-calculator')
             ->section('settings');

    Or via PHP 8 Attribute on the Model:
        #[Field(type: 'view', section: 'settings', view: 'shopProduct::admin.components.price-calculator')]
        public string $price_preview; // virtual — no DB column required
--}}
@if(!empty($field->userType) && View::exists($field->userType))
    @include($field->userType, [
        'model'    => $model ?? null,
        'field'    => $field,
        'module'   => $module,
        'formData' => $formData,
    ])
@else
    {{-- Fallback: show a developer hint if the view path is wrong or not set --}}
    @if(config('app.debug'))
        <div class="alert alert-warning py-1 px-2 my-2" style="font-size: 0.82rem;">
            <strong>[Nexus View Slot]</strong>
            Field <code>{{ $field->name }}</code> has type <code>view</code>,
            but the view <code>{{ $field->userType ?? '(not set)' }}</code> was not found.
            Set <code>#[Field(type: 'view', view: 'module::path.to.view')]</code>
            or <code>->view('module::path')</code> in your configuration.
        </div>
    @endif
@endif
