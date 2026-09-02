@php
    $fieldName = $field->relationConfig['path_field'] ?? 'url';
@endphp

<div id="images_selector" class="mb-4 rounded-2xl border border-gray-200 dark:border-gray-800">
    <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">@lang(lcfirst($module->name).'::translate.'.$field->name)</h3>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
            <div v-for="(image, key) in images" :key="`image-${key}`">
                <div class="flex flex-col items-center gap-2 rounded-lg border border-gray-200 p-2 dark:border-gray-800">
                    <div v-if="image[fieldName]" class="flex h-50 w-full items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
                        <a :href="image[fieldName]" target="_blank" rel="noopener">
                            <img :src="image[fieldName]" class="max-h-50 w-full object-contain"/>
                        </a>
                    </div>

                    <div class="flex w-full gap-1.5">
                        <button v-if="!isDisabled" type="button"
                                class="popup_selector flex-1 rounded-lg border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5"
                                :data-inputid="'image_select_' + key">
                            <i class="{{ nexus_icon('edit') }}"></i>
                        </button>

                        <input v-if="image[fieldName]"
                               type="hidden"
                               :name="`relation[images][${key}][id]`"
                               :value="image.id">

                        <input type="hidden"
                               :id="'image_select_' + key"
                               :name="`relation[images][${key}][${fieldName}]`"
                               :value="image[fieldName]"
                               @click="setImage(key, $event.target.value)">

                        <input v-if="image[fieldName]"
                               type="hidden"
                               :name="`relation[images][${key}][align]`"
                               :value="image.align">

                        <button v-if="!isDisabled" type="button"
                                @click="deleteImage(key)"
                                class="flex-1 rounded-lg border border-error-200 px-2 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
                            <i class="{{ nexus_icon('delete') }}"></i>
                        </button>
                    </div>

                    <div class="w-full" v-if="image[fieldName]">
                        <label class="mb-1.5 block text-center text-xs text-gray-500 dark:text-gray-400">@lang(lcfirst($module->name).'::translate.align')</label>
                        <div class="flex flex-wrap justify-center gap-1" v-for="(chunk, rowIndex) in chunkedAlignments" :key="rowIndex">
                            <label v-for="(value, index) in chunk" :key="index"
                                :title="value"
                                class="flex h-6 w-6 items-center justify-center rounded border border-gray-300 text-[9px] has-checked:border-brand-500 has-checked:bg-brand-500 dark:border-gray-700">
                                <input class="sr-only" type="radio"
                                    :id="'align_' + (rowIndex * 3 + index) + '_' + key"
                                    :name="'align_radio_' + key" :value="value" v-model="image.align"
                                    :disabled="isDisabled">
                            </label>
                        </div>
                        <div class="mt-1 text-center">
                            <small class="text-[10px] text-gray-400">@{{ image.align }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/vue@^3.4.0"></script>
<script>
    let imagesInput = [];
    let groupedAlignments = @json(\App\Nexus\Modules\PhotoUserHistory\Enums\AlignEnum::cases());
    const fieldName = @json($fieldName);

    @if(isset($model))
            @php
                $imagesInput = $model->images->map(function ($img) use ($fieldName) {
                    return [
                        'id' => $img->id,
                         $fieldName => $img->{$fieldName},
                        'align' => $img->align ?? 'center center',
                    ];
                })->values();
            @endphp

        imagesInput = @json($imagesInput);
    @endif

    const ImagesSelector = {
        data() {
            return {
                images: [],
                currentIndex: 0,
                fieldName: fieldName,
                groupedAlignments: groupedAlignments,
                chunkedAlignments: [],
                isDisabled: @json($field->isDisabledForAction($action ?? null)),
            }
        },

        mounted() {
            this.images = imagesInput.length ? imagesInput : [];
            if (!this.isDisabled) {
                this.images.push(this.emptyImage());
            }

            for (let i = 0; i < this.groupedAlignments.length; i += 3) {
                this.chunkedAlignments.push(this.groupedAlignments.slice(i, i + 3));
            }

            document.addEventListener("fileChoose", (event) => {
                if (!event.detail?.isSingle) {
                    this.setImage(this.currentIndex, event.detail[fieldName]);
                }
            });
        },

        methods: {

            emptyImage() {
                return {
                    id: null,
                    [fieldName]: '',
                    align: 'center center'
                };
            },

            setImage(key, value) {
                if (!value) return;

                this.images[key][fieldName] = this.normalize(value);

                if (key === this.images.length - 1) {
                    this.images.push(this.emptyImage());
                }
            },

            deleteImage(key) {
                this.images.splice(key, 1);

                if (this.images.length === 0 ||
                    this.images[this.images.length - 1][fieldName]) {
                    this.images.push(this.emptyImage());
                }
            },

            normalize(url) {
                return url.startsWith('/') ? url : '/' + url;
            }
        }
    };

    Vue.createApp(ImagesSelector).mount('#images_selector');
</script>
