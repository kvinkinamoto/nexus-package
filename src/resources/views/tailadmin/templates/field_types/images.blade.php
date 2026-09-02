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
                <div class="flex flex-col items-center gap-2">
                    <div v-if="image[fieldName]" class="flex h-37.5 w-full items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
                        <a :href="image[fieldName]" target="_blank" rel="noopener">
                            <img :src="image[fieldName]" class="max-h-37.5 w-full object-contain"/>
                        </a>
                    </div>

                    <div v-if="!isDisabled" class="flex w-full gap-1.5">
                        <button type="button"
                                class="popup_selector flex-1 rounded-lg border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5"
                                :data-inputid="'image_select_' + key">
                            <i class="{{ nexus_icon('edit') }}"></i>
                        </button>

                        <input type="hidden"
                               :name="`relation[images][${key}][id]`"
                               :value="image.id">

                        <input type="hidden"
                               :id="'image_select_' + key"
                               :name="`relation[images][${key}][${fieldName}]`"
                               :value="image[fieldName]"
                               @click="setImage(key, $event.target.value)">

                        <button type="button"
                                @click="deleteImage(key)"
                                class="flex-1 rounded-lg border border-error-200 px-2 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
                            <i class="{{ nexus_icon('delete') }}"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/vue@^3.4.0"></script>
<script>
    let imagesInput = [];
    const fieldName = @json($fieldName);

    @if(isset($model))
            @php
                $imagesInput = $model->images->map(function ($img) use ($fieldName) {
                    return [
                        'id' => $img->id,
                        $fieldName => $img->{$fieldName},
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
                isDisabled: @json($field->isDisabledForAction($action ?? null)),
            }
        },

        mounted() {
            this.images = imagesInput.length ? imagesInput : [];
            if (!this.isDisabled) {
                this.images.push(this.emptyImage());
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
                    [fieldName]: ''
                };
            },

            setImage(key, value) {
                if (!value) return;

                this.images[key][fieldName] = this.normalize(value);

                if (!this.isDisabled && key === this.images.length - 1) {
                    this.images.push(this.emptyImage());
                }
            },

            deleteImage(key) {
                if (this.isDisabled) return;
                this.images.splice(key, 1);

                if (this.images.length === 0 ||
                    (this.images[this.images.length - 1][fieldName])) {
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
