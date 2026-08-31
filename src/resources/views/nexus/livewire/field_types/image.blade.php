{{--
    Livewire Етап 6 — single-image field, backed by the same elFinder popup
    the legacy field_types/image.blade.php uses (see public/packages/barryvdh/
    elfinder/js/standalonepopup.js): no Livewire file upload here at all,
    elFinder does the actual upload/browse in its own iframe and calls back
    processSelectedFile(path, fieldId), which writes the chosen path into the
    input below by id — wire:model picks it up via the 'input' event that
    callback now also fires. The path itself is persisted server-side by
    App\Nexus\Modules\Images\Traits\ProcessesImages (called from the module's
    Hooks\Store/Update, same as the legacy path — see ModuleForm::save()).
--}}
@php
    $errorKey = "data.{$field->name}";
    $value = $this->data[$field->name] ?? null;
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    {{-- type="text"/d-none, not type="hidden" — Livewire doesn't attach its
         change-tracking listener to hidden inputs at all, so
         processSelectedFile()'s 'input' trigger would otherwise never reach
         wire:model. --}}
    <input type="text" class="d-none" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}">

    <div class="d-flex align-items-center gap-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-center flex-shrink-0"
             style="width: 80px; height: 80px; overflow: hidden;">
            <img src="/{{ ltrim($value ?? 'nexus/images/no-image.jpg', '/') }}"
                 class="img-fluid rounded" style="max-width: 80px; max-height: 80px; object-fit: contain;">
        </div>
        <div class="d-flex flex-column gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary popup_selector" data-inputid="field-{{ $field->name }}">
                @lang('nexus::translate.chooseImage')
            </button>
            @if($value)
                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="clearImage('{{ $field->name }}')">
                    @lang('nexus::translate.remove')
                </button>
            @endif
        </div>
    </div>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
