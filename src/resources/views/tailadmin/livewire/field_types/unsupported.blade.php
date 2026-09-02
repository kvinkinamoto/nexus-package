{{--
    Field types not yet ported to the Livewire form pipeline (Фаза 8.2,
    Livewire Етап 3 pilot scope: 'string', 'relation' (single belongsTo,
    ajax only) and #[RepeaterField]-bearing fields). Visible rather than
    silently dropped, matching templates/field_types/unknown.blade.php's own
    "never disappear silently" convention — a module needing a type beyond
    this pilot's scope isn't safe to flag #[Module(livewire: true)] yet.
--}}
<div class="mb-4">
    <div class="rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-sm text-warning-700 dark:border-warning-800 dark:bg-warning-500/10 dark:text-warning-400">
        @lang('nexus::translate.field') "{{ $field->name }}" ({{ $field->type }}):
        не підтримується у Livewire-формі на цьому етапі.
    </div>
</div>
