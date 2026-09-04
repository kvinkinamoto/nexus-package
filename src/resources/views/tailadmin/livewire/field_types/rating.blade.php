{{--
    Built-in star-rating control — promotes the pattern
    App\Nexus\Modules\Demo's own 'ratingStars' custom type demonstrated (see
    that view's docblock: it existed only to prove the module-scoped
    override/custom-type hook works) into a real core type, so a module
    doesn't need to hand-write this itself. Max stars configurable via
    $field->customData['max'] (default 5).
--}}
@php
    $errorKey = "data.{$field->name}";
    $max = ($field->customData ?? [])['max'] ?? 5;
    $current = (int) ($this->data[$field->name] ?? 0);
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="flex items-center gap-1">
        @for($i = 1; $i <= $max; $i++)
            <button type="button" wire:click="$set('data.{{ $field->name }}', {{ $i }})"
                @disabled($field->isDisabledForAction($action ?? null))
                class="text-2xl leading-none disabled:cursor-not-allowed disabled:opacity-50 {{ $i <= $current ? 'text-warning-500' : 'text-gray-300 dark:text-gray-700' }}">
                &#9733;
            </button>
        @endfor
        @if($current > 0)
            <button type="button" wire:click="$set('data.{{ $field->name }}', null)"
                class="ml-2 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                @lang('nexus::translate.cancel')
            </button>
        @endif
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
