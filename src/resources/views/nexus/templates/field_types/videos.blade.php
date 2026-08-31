@php
    $videoFieldName = $field->relationConfig['path_field'] ?? 'url';
@endphp

<div class="row" id="videos_selector">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">@lang(lcfirst($module->name).'::translate.'.$field->name)</h3>
            </div>

            <div class="card-body pad table-responsive">
                <div class="row">
                    <div class="col-md-12 row">
                        <div class="col-md-3 mb-3"
                             v-for="(video, key) in videos"
                             :key="`video-${key}`">

                            <div class="form-group">
                                <div class="input-group mb-12 justify-content-center">

                                    <!-- LOCAL VIDEO -->
                                    <video v-if="isLocalVideo(video[videoFieldName])"
                                           controls
                                           class="img-fluid mb-1 col-12"
                                           :src="video[videoFieldName]">
                                    </video>

                                    <!-- EMBED -->
                                    <iframe v-else-if="isEmbed(video[videoFieldName])"
                                            class="mb-3 col-12"
                                            height="180"
                                            :src="embedUrl(video[videoFieldName])"
                                            frameborder="0"
                                            allowfullscreen>
                                    </iframe>

                                    <div class="btn-group gap-1 w-100">

                                        <button v-if="!isDisabled" type="button"
                                                class="btn btn-primary popup_selector btn-sm"
                                                :data-inputid="'video_select_' + key">
                                            <i class="{{ nexus_icon('edit') }}"></i>
                                        </button>

                                        <!-- ID (якщо є) -->
                                        <input type="hidden"
                                               :name="`relation[videos][${key}][id]`"
                                               :value="video.id">

                                        <!-- URL -->
                                        <input type="hidden"
                                               :id="'video_select_' + key"
                                               :name="`relation[videos][${key}][${videoFieldName}]`"
                                               :value="video[videoFieldName]"
                                               @click="setVideo(key, $event.target.value)">

                                        <!-- ПРИКЛАД ДОВІЛЬНОГО ПОЛЯ -->
                                        {{--                                        <input type="hidden"--}}
                                        {{--                                               :name="`relation[videos][${key}][status]`"--}}
                                        {{--                                               :value="video.status ?? 'ready'">--}}

                                        <button v-if="!isDisabled" type="button"
                                                @click="deleteVideo(key)"
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
<script>
    let videosInput = [];
    const videoFieldName = @json($videoFieldName);

    @if(isset($model))
            @php
                $videosInput = $model->videos->map(function ($v) use ($videoFieldName) {
                    return [
                        'id' => $v->id,
                        $videoFieldName => $v->{$videoFieldName},
    //                    'status' => $v->status ?? 'ready',
                    ];
                })->values();
            @endphp

        videosInput = @json($videosInput);
    @endif
    const VideosSelector = {
        data() {
            return {
                videos: [],
                currentIndex: 0,
                videoFieldName: videoFieldName,
                isDisabled: @json($field->isDisabledForAction($action ?? null)),
            }
        },

        mounted() {
            this.videos = videosInput.length
                ? videosInput
                : [];

            if (!this.isDisabled) {
                this.videos.push(this.emptyVideo());
            }

            document.addEventListener("fileChoose", (event) => {
                if (!event.detail.isSingle) {
                    this.setVideo(this.currentIndex, event.detail[videoFieldName]);
                }
            });
        },

        methods: {

            emptyVideo() {
                return {
                    id: null,
                    [videoFieldName]: ''
                    // status: 'ready'
                };
            },

            setVideo(key, value) {
                if (!value) return;

                this.videos[key][videoFieldName] = this.normalize(value);

                if (key === this.videos.length - 1) {
                    this.videos.push(this.emptyVideo());
                }
            },

            deleteVideo(key) {
                this.videos.splice(key, 1);
            },

            normalize(url) {
                return url.startsWith('/') ? url : '/' + url;
            },

            isLocalVideo(url) {
                return url && !url.startsWith('http');
            },

            isEmbed(url) {
                return url?.includes('youtube')
                    || url?.includes('youtu.be')
                    || url?.includes('vimeo');
            },

            embedUrl(url) {
                if (!url) return '';
                if (url.includes('youtube')) {
                    return 'https://www.youtube.com/embed/' +
                        url.split('v=')[1]?.split('&')[0];
                }
                if (url.includes('youtu.be')) {
                    return 'https://www.youtube.com/embed/' + url.split('/').pop();
                }
                if (url.includes('vimeo')) {
                    return 'https://player.vimeo.com/video/' + url.split('/').pop();
                }
                return '';
            }
        }
    };

    Vue.createApp(VideosSelector).mount('#videos_selector');
</script>
