@php
    $errorKey = "relationRows.{$field->name}.{$index}.{$column->name}";
@endphp
<input type="text" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}"
    class="form-control @error($errorKey) is-invalid @enderror">
@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
