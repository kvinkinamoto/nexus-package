{{--
    Livewire Етап 6 — single-image field, backed by the same elFinder popup
    the legacy field_types/image.blade.php uses (see layouts/adminpanel.blade.php's
    #nexusElfinderModal): no Livewire file upload here at all, elFinder does
    the actual upload/browse in an iframe and calls back
    processSelectedFile(path, fieldId), which writes the chosen path into the
    input below by id — wire:model picks it up via the 'input' event that
    callback fires. The path itself is persisted server-side by
    App\Nexus\Modules\Images\Traits\ProcessesImages (called from the module's
    Hooks\Store/Update, same as the legacy path — see ModuleForm::save()).
--}}
@php
    $errorKey = "data.{$field->name}";
    $value = $this->data[$field->name] ?? null;
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    {{-- type="text"/hidden-via-class, not type="hidden" — Livewire doesn't attach its
         change-tracking listener to hidden inputs at all, so
         processSelectedFile()'s 'input' trigger would otherwise never reach
         wire:model. --}}
    <input type="text" class="hidden" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}">

    <div class="flex items-center gap-3">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
            <img src="/{{ ltrim($value ?? 'nexus/images/no-image.jpg', '/') }}"
                 class="max-h-20 max-w-20 object-contain">
        </div>
        <div class="flex flex-col gap-2">
            <button type="button" class="popup_selector rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5" data-inputid="field-{{ $field->name }}">
                @lang('nexus::translate.chooseImage')
            </button>
            @if($value)
                <button type="button" wire:click="clearImage('{{ $field->name }}')"
                    class="rounded-lg border border-error-200 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
                    @lang('nexus::translate.remove')
                </button>
            @endif
        </div>
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
