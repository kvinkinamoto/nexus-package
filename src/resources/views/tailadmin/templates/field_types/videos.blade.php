@php
    $videoFieldName = $field->relationConfig['path_field'] ?? 'url';
@endphp

<div id="videos_selector" class="mb-4 rounded-2xl border border-gray-200 dark:border-gray-800">
    <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">@lang(lcfirst($module->name).'::translate.'.$field->name)</h3>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
            <div v-for="(video, key) in videos" :key="`video-${key}`">
                <div class="flex flex-col items-center gap-2">
                    <video v-if="isLocalVideo(video[videoFieldName])"
                           controls
                           class="mb-1 w-full rounded-lg"
                           :src="video[videoFieldName]">
                    </video>

                    <iframe v-else-if="isEmbed(video[videoFieldName])"
                            class="mb-3 w-full rounded-lg"
                            height="180"
                            :src="embedUrl(video[videoFieldName])"
                            frameborder="0"
                            allowfullscreen>
                    </iframe>

                    <div class="flex w-full gap-1.5">
                        <button v-if="!isDisabled" type="button"
                                class="popup_selector flex-1 rounded-lg border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5"
                                :data-inputid="'video_select_' + key">
                            <i class="{{ nexus_icon('edit') }}"></i>
                        </button>

                        <input type="hidden"
                               :name="`relation[videos][${key}][id]`"
                               :value="video.id">

                        <input type="hidden"
                               :id="'video_select_' + key"
                               :name="`relation[videos][${key}][${videoFieldName}]`"
                               :value="video[videoFieldName]"
                               @click="setVideo(key, $event.target.value)">

                        <button v-if="!isDisabled" type="button"
                                @click="deleteVideo(key)"
                                class="flex-1 rounded-lg border border-error-200 px-2 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
                            <i class="{{ nexus_icon('delete') }}"></i>
                        </button>
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
