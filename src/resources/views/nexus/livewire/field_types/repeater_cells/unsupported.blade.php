{{--
    Fallback for a #[RepeaterField] column type with neither a module
    override ({module}::admin.livewire_field_types.{type}) nor a package
    built-in — visible on purpose, matching field_types/unknown.blade.php's
    "never disappear silently" convention.
--}}
<div class="text-danger small">
    "{{ $column->name }}" ({{ $column->type }}): не підтримується у Livewire-репітері.
</div>
