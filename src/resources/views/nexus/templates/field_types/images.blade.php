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
                        <div class="col-md-3 mb-3"
                             v-for="(image, key) in images"
                             :key="`image-${key}`">

                            <div class="form-group">
                                <div class="input-group mb-12 justify-content-center">

                                    <div v-if="image[fieldName]" class="bg-light rounded d-flex align-items-center justify-content-center mb-1" style="width: 100%; height: 150px; overflow: hidden;">
                                        <a :href="image[fieldName]" class="image-popup">
                                            <img :src="image[fieldName]"
                                                 class="img-fluid rounded" style="max-height: 150px; object-fit: contain;"/>
                                        </a>
                                    </div>

                                    <div v-if="!isDisabled" class="btn-group gap-1 w-100">

                                        <button type="button"
                                                class="btn btn-primary popup_selector btn-sm"
                                                :data-inputid="'image_select_' + key">
                                            <i class="{{ nexus_icon('edit') }}"></i>
                                        </button>

                                        <!-- ID -->
                                        <input type="hidden"
                                               :name="`relation[images][${key}][id]`"
                                               :value="image.id">

                                        <!-- URL -->
                                        <input type="hidden"
                                               :id="'image_select_' + key"
                                               :name="`relation[images][${key}][${fieldName}]`"
                                               :value="image[fieldName]"
                                               @click="setImage(key, $event.target.value)">

                                        <button type="button"
                                                @click="deleteImage(key)"
                                                class="btn btn-danger btn-sm">
                                            <i class="{{ nexus_icon('delete') }}"></i>
                                        </button>

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
