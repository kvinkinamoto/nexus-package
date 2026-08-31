@php
    $fieldName = $field->relationConfig['path_field'] ?? 'url';
@endphp

<div class="row" id="images_selector">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">@lang(lcfirst($module->name).'::translate.'.$field->name)</h3>
            </div>

            <div class="card-body pad table-responsive">
                <div class="row">
                    <div class="col-md-12 row">
                        <div class="col-md-3"
                             v-for="(image, key) in images"
                             :key="`image-${key}`">

                            <div class="form-group border p-2 mb-3">
                                <div class="input-group mb-12 justify-content-center flex-column align-items-center">

                                    <div v-if="image[fieldName]" class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="width: 100%; height: 200px; overflow: hidden;">
                                        <a :href="image[fieldName]" class="image-popup">
                                            <img :src="image[fieldName]"
                                                 class="img-fluid rounded" style="max-height: 200px; object-fit: contain;"/>
                                        </a>
                                    </div>

                                    <div class="btn-group gap-1 mb-3">

                                        <button v-if="!isDisabled" type="button"
                                                class="btn btn-primary popup_selector btn-sm"
                                                :data-inputid="'image_select_' + key">
                                            <i class="{{ nexus_icon('edit') }}"></i>
                                        </button>

                                        <!-- ID -->
                                        <input v-if="image[fieldName]"
                                               type="hidden"
                                               :name="`relation[images][${key}][id]`"
                                               :value="image.id">

                                        <!-- URL -->
                                        <input type="hidden"
                                               :id="'image_select_' + key"
                                               :name="`relation[images][${key}][${fieldName}]`"
                                               :value="image[fieldName]"
                                               @click="setImage(key, $event.target.value)">

                                        <!-- Align -->
                                        <input v-if="image[fieldName]"
                                               type="hidden"
                                               :name="`relation[images][${key}][align]`"
                                               :value="image.align">

                                        <button v-if="!isDisabled" type="button"
                                                @click="deleteImage(key)"
                                                class="btn btn-danger btn-sm">
                                            <i class="{{ nexus_icon('delete') }}"></i>
                                        </button>

                                    </div>

                                    <div class="alignment-selector w-100" v-if="image[fieldName]">
                                        <label class="form-label d-block text-center small mb-2">@lang(lcfirst($module->name).'::translate.align')</label>
                                        <div class="row g-1 justify-content-center" v-for="(chunk, rowIndex) in chunkedAlignments" :key="rowIndex">
                                            <div class="col-4 text-center" v-for="(value, index) in chunk" :key="index">
                                                <div class="form-check form-check-inline m-0 p-0">
                                                    <input
                                                            class="btn-check"
                                                            type="radio"
                                                            :id="'align_' + (rowIndex * 3 + index) + '_' + key"
                                                            :name="'align_radio_' + key"
                                                            :value="value"
                                                            v-model="image.align"
                                                            :disabled="isDisabled"
                                                    >
                                                    <label
                                                            class="btn btn-outline-secondary btn-sm p-0"
                                                            :for="'align_' + (rowIndex * 3 + index) + '_' + key"
                                                            :title="value"
                                                            style="width: 24px; height: 24px;"
                                                    >
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mt-1">
                                            <small class="text-muted" style="font-size: 10px;">@{{ image.align }}</small>
                                        </div>
                                    </div>

                                </div>
                            </div>
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

            // Chunk alignments into rows of 3
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
