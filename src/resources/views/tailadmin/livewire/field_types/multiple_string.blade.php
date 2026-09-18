{{--
    Port of templates/field_types/multiple_string.blade.php — that legacy
    partial wrapped a plain text input in Choices.js (comma-separated tags
    input). Rebuilt here as a plain Alpine chip list synced to a Livewire
    array property via $wire.entangle(), same "no library that fights
    Livewire's DOM morphing" reasoning documented on text.blade.php's own
    CKEditor branch (see .ai/rules/views.md) — Choices.js is exactly the
    library that caused that problem elsewhere in this pipeline.
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 flex-1 min-w-[8rem] rounded-lg border-0 bg-transparent px-2 py-2 text-sm text-gray-800 focus:outline-hidden focus:ring-0 dark:text-white/90';
@endphp
<div class="mb-4" x-data="{ tags: $wire.entangle('data.{{ $field->name }}'), draft: '' }">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-300 bg-transparent px-2 py-1.5 dark:border-gray-700 {{ $errors->has($errorKey) ? 'border-error-500' : '' }}">
        <template x-for="(tag, index) in tags" :key="index">
            <span class="flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-800 dark:bg-white/5 dark:text-gray-300">
                <span x-text="tag"></span>
                <button type="button" @click="tags.splice(index, 1)" @disabled($field->isDisabledForAction($action ?? null))
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="bx bx-x text-xs"></i>
                </button>
            </span>
        </template>

        @unless($field->isDisabledForAction($action ?? null))
            <input type="text" x-model="draft" id="field-{{ $field->name }}"
                @keydown.enter.prevent="if (draft.trim()) { tags.push(draft.trim()); draft = ''; }"
                @keydown.,.prevent="if (draft.trim()) { tags.push(draft.trim()); draft = ''; }"
                placeholder="@lang('nexus::translate.addTag')"
                class="{{ $inputClass }}">
        @endunless
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
