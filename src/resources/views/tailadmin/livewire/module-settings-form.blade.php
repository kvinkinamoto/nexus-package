{{--
    Renders whatever #[Setting(...)] attributes a module declares (see
    Attributes\Setting), backed by Nodex\Nexus\Livewire\ModuleSettingsForm.
    One field per setting.type — string/text/boolean/integer/select/image are
    the types in real use today (see App\Nexus\Modules\Settings\
    ModuleConfiguration for a live example of every one). Labels follow the
    exact same {module}::translate.{lowercase label} lookup as field_types/
    _label.blade.php, so a new setting's translate.php entry needs the same
    lowercase-with-space key — see feedback-nexus-module-attribute-gotchas.
--}}
<div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.02]">
        @error('form')
            <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-800 dark:bg-error-500/10 dark:text-error-400">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit.prevent="save">
            @foreach($settings as $key => $setting)
                @php $errorKey = "data.{$key}"; @endphp

                <div class="mb-5">
                    @if($setting->type === 'boolean')
                        <label class="flex cursor-pointer items-center gap-2.5">
                            <span class="relative inline-flex items-center">
                                <input type="checkbox" id="setting-{{ $key }}" wire:model="data.{{ $key }}" class="peer sr-only">
                                <span class="h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700"></span>
                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                            </span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-400">
                                @lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($setting->label))
                            </span>
                        </label>
                    @else
                        <label for="setting-{{ $key }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            @lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($setting->label))
                            @if($setting->isRequired) <span class="text-error-500">*</span> @endif
                        </label>

                        @if($setting->type === 'text')
                            <textarea id="setting-{{ $key }}" wire:model="data.{{ $key }}" rows="4"
                                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-none focus:border-brand-300 dark:border-gray-700 dark:text-white/90"></textarea>
                        @elseif($setting->type === 'select')
                            <select id="setting-{{ $key }}" wire:model="data.{{ $key }}"
                                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-none focus:border-brand-300 dark:border-gray-700 dark:text-white/90">
                                @foreach($setting->options as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif($setting->type === 'integer')
                            <input type="number" id="setting-{{ $key }}" wire:model="data.{{ $key }}"
                                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-none focus:border-brand-300 dark:border-gray-700 dark:text-white/90">
                        @elseif($setting->type === 'image')
                            <input type="text" class="hidden" id="setting-{{ $key }}" wire:model="data.{{ $key }}">
                            <div class="flex items-center gap-3">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
                                    <img src="/{{ ltrim($data[$key] ?? 'nexus/images/no-image.jpg', '/') }}" class="max-h-20 max-w-20 object-contain">
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button type="button" class="popup_selector rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5" data-inputid="setting-{{ $key }}">
                                        @lang('nexus::translate.chooseImage')
                                    </button>
                                    @if($data[$key] ?? null)
                                        <button type="button" wire:click="clearImage('{{ $key }}')"
                                            class="rounded-lg border border-error-200 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
                                            @lang('nexus::translate.remove')
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <input type="text" id="setting-{{ $key }}" wire:model="data.{{ $key }}"
                                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-none focus:border-brand-300 dark:border-gray-700 dark:text-white/90">
                        @endif
                    @endif

                    @if($setting->comment)
                        <p class="mt-1.5 text-xs text-gray-400">{{ $setting->comment }}</p>
                    @endif

                    @error($errorKey)
                        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-4 dark:border-white/5">
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:opacity-60">
                    <span wire:loading wire:target="save" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    @lang('nexus::translate.save')
                </button>
            </div>
        </form>
    </div>
</div>
