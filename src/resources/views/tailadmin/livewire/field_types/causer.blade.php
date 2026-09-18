{{--
    Livewire Етап 6 — port of ActivityLog's own custom_index_fields/causer.blade.php
    (table-column version) as a read-only form field. causer_type/causer_id
    are both plain 'string' fields already present in $this->data (mount()'s
    generic fallback), so this resolves the actual causer model straight from
    them rather than needing a #[Relation] or a ModuleForm change.
--}}
@php
    $causerType = $this->data['causer_type'] ?? null;
    $causerId = $this->data['causer_id'] ?? null;
    $causer = ($causerType && $causerId && class_exists($causerType)) ? $causerType::find($causerId) : null;
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    @if($causer instanceof \App\Models\User)
        <div>
            <a href="{{ route('nexus.module.action', ['user', 'edit', 'id' => $causer->id]) }}" target="_blank" class="text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">
                {{ $causer->email }} <span class="text-gray-400">(ID: {{ $causer->id }})</span>
            </a>
        </div>
    @else
        <div class="text-sm text-gray-400">
            @lang(Str::lcfirst($module->name) . '::translate.system') / @lang(Str::lcfirst($module->name) . '::translate.deleted_user')
        </div>
    @endif
</div>
