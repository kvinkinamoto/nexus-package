{{--
    Field types not yet ported to the Livewire form pipeline (Фаза 8.2,
    Livewire Етап 3 pilot scope: 'string', 'relation' (single belongsTo,
    ajax only) and #[RepeaterField]-bearing fields). Visible rather than
    silently dropped, matching templates/field_types/unknown.blade.php's own
    "never disappear silently" convention — a module needing a type beyond
    this pilot's scope isn't safe to flag #[Module(livewire: true)] yet.
--}}
<div class="mb-3">
    <div class="alert alert-warning py-2 px-3 mb-0">
        @lang('nexus::translate.field') "{{ $field->name }}" ({{ $field->type }}):
        не підтримується у Livewire-формі на цьому етапі.
    </div>
</div>
