{{--
    #[Field(type: 'gallery')] — a real media-library-backed collection
    (dedup, thumbnails, reordering) via MediaLibraryInterface, unlike
    #[Field(type: 'images')]'s plain JSON-array-of-paths column. See
    Concerns/ManagesGalleryFields's docblock for why this needs $this->id
    already set (files attach directly to the persisted model, not staged in
    $this->data like every other field type here) and
    MediaLibraryInterface's docblock for how the storage backend is
    swappable / how to disable this field type entirely.
--}}
@if(! config('nexus.media_library.enabled', true))
    <div class="mb-4 rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-sm text-warning-700 dark:border-warning-800 dark:bg-warning-500/10 dark:text-warning-400">
        @lang('nexus::translate.field') "{{ $field->name }}": media library is disabled
        (config('nexus.media_library.enabled')).
    </div>
@elseif(! $this->id)
    <div class="mb-4">
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])
        <p class="text-xs text-gray-400">@lang('nexus::translate.save_first_for_gallery')</p>
    </div>
@else
    @php
        $items = $this->galleryItems($field->name);
        $isDisabled = $field->isDisabledForAction($action ?? null);
    @endphp
    <div class="mb-4">
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

        @if($items->isNotEmpty())
            <div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                @foreach($items as $i => $item)
                    <div class="relative" wire:key="{{ $field->name }}-{{ $item->id }}">
                        <div class="flex h-24 w-full items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
                            <img src="{{ $item->thumbnailUrl ?? $item->url }}" class="h-24 w-full object-cover" title="{{ $item->name }}">
                        </div>
                        @unless($isDisabled)
                            <button type="button" wire:click="deleteGalleryItem('{{ $field->name }}', '{{ $item->id }}')"
                                aria-label="@lang('nexus::translate.remove')"
                                class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-error-500 text-xs text-white shadow-theme-sm hover:bg-error-600">
                                <i class="bx bx-x"></i>
                            </button>
                            <div class="mt-1 flex items-center justify-center gap-1">
                                <button type="button" @if($i === 0) disabled @endif
                                    wire:click="reorderGalleryItem('{{ $field->name }}', '{{ $item->id }}', 'up')"
                                    class="flex h-5 w-5 items-center justify-center rounded text-gray-400 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                                    <i class="bx bx-chevron-left"></i>
                                </button>
                                <button type="button" @if($i === $items->count() - 1) disabled @endif
                                    wire:click="reorderGalleryItem('{{ $field->name }}', '{{ $item->id }}', 'down')"
                                    class="flex h-5 w-5 items-center justify-center rounded text-gray-400 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                                    <i class="bx bx-chevron-right"></i>
                                </button>
                            </div>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif

        @unless($isDisabled)
            <input type="file" wire:model="galleryUpload.{{ $field->name }}" accept="image/*"
                class="text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-gray-700 dark:text-gray-400 dark:file:bg-white/5 dark:file:text-gray-300">
            <span wire:loading wire:target="galleryUpload.{{ $field->name }}" class="ml-2 text-xs text-gray-400">
                @lang('nexus::translate.start')...
            </span>
        @endunless
    </div>
@endif
