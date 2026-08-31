@extends('nexus::'. config('nexus.template').'.layouts.adminpanel')

@section('mainContent')
    <div class="container mt-5">
        <h1>{{ $model??null ? 'Edit' : 'Create' }} {{ ucfirst($module->name) }}</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              onsubmit="return sendFormConfirm()"
              action="{{ route('nexus.module.action', ['module' => $module->name, 'action' => $action, 'id' => $model->id ?? null]) }}">
            @csrf
            @method($method)

            @foreach ($formData['fields'] as $field)
                <div class="mb-3">
                    <label for="{{ $field->name }}" class="form-label">{{ $field->label }}</label>
                    <input type="{{ $field->type }}" name="{{ $field->name }}" id="{{ $field->name }}"
                           value="{{ old($field->name, $model->{$field->name} ?? '') }}"
                           class="form-control @error($field->name) is-invalid @enderror">
                    @error($field->name)
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route("nexus.module.action", ['module' => $moduleConfig->name, 'action'=>'index']) }}"
               class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
@section('js')
    @parent
    <script>
        function sendFormConfirm(id) {
            var r = confirm("You sure to delete?");
            if (r === true) {
                return true;
                // document.forms[id].submit();
            } else {
                return false;
            }
        }
    </script>
@stop
