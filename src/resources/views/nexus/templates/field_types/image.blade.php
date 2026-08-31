@php
    $value = '';
    if (isset($tab_lang)) {
        if (isset($model)) {
            // Handle translated fields; for image use relationLoaded to get path
            if ($field->name === 'image' && $model->relationLoaded('image') && $model->image) {
                $value = old($field->name . '.' . $tab_lang, $model->image->path ?? null);
            } else {
                $value = old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)));
            }
        } else {
            $value = old($field->name . '.' . $tab_lang, $field->defaultValue ?? null);
        }
    } else {
        if (isset($model)) {
            if ($field->name === 'image' && $model->relationLoaded('image') && $model->image) {
                $value = old($field->name, $model->image->path ?? null);
            } else {
                $value = old($field->name, ($model->{$field->name}));
            }
        } else {
            $value = old($field->name, $field->defaultValue ?? null);
        }
    }
    if (!isset($value)) {
        $value = $field->defaultValue ?? null;
    }
@endphp

<div class="fallback">
    <label for="{{$field->name}}" class="form-label">
        @if(isset($field->label) && !empty($field->label))
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        @else
            @if(str_contains($field->name, '::'))
                @lang($field->name)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->name)
            @endif {{$tab_lang ?? ''}}
        @endif
        @if($field->isRequired ?? false)
            *
        @endif
    </label>
    @if($field->isDisabledForAction($action ?? null))
        <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$value}}">
    @endif
    <input type="text" onchange="" id="{{$field->name . '_' . ($tab_lang ?? '')}}" @if(isset($tab_lang))
    name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$value}}"
        @if($field->isDisabledForAction($action ?? null)) disabled @endif
        class="form-control @error($field->name . '.' . ($tab_lang ?? '')) is-invalid @enderror">
    {!! $errors->first($field->name . '.' . ($tab_lang ?? ''), '<small class="error invalid-feedback">:message</small>') !!}
</div>
<div class="dropzone mb-2 @if(!($field->isDisabledForAction($action ?? null))) dz-clickable @endif" @if($field->isDisabledForAction($action ?? null)) style="pointer-events: none; opacity: 0.6;" @endif>
    <div class="dz-message needsclick popup_selector" data-inputid="{{$field->name . '_' . ($tab_lang ?? '')}}">
        <i class="{{ nexus_icon('upload') }} fs-48 text-primary"></i>
        <h3 class="mt-4">
            @if($field->isDisabledForAction($action ?? null))
                Image browsing is disabled
            @else
                Click to browse your images <span class="text-primary">here</span>
            @endif
        </h3>
        <span class="text-muted fs-13">
            1600 x 1200 (4:3) recommended. PNG, JPG and GIF files are allowed
        </span>

    </div>
</div>

<ul class="list-unstyled mb-3 @if(empty($value)) d-none @endif"
    id="dropzone-preview{{$field->name . '_' . ($tab_lang ?? '')}}">
    <li class="mt-2" id="dropzone-preview-list{{$field->name . '_' . ($tab_lang ?? '')}}">
        <!-- This is used as the file preview template -->
        <div class="border rounded">
            <div class="d-flex p-2">
                <div class="flex-shrink-0 me-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; overflow: hidden;">
                        <a href="/{{is_string($value) ? $value : \Nodex\Nexus\helpers\Image::getStorageImage($value)}}" 
                           class="image-popup" id="{{$field->name . '_' . ($tab_lang ?? '')}}_link">
                            <img src="/{{is_string($value) ? $value : \Nodex\Nexus\helpers\Image::getStorageImage($value)}}"
                                id="{{$field->name . '_' . ($tab_lang ?? '')}}_prev"
                                class="img-fluid rounded d-block" style="max-width: 80px; max-height: 80px; object-fit: contain;">
                        </a>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="pt-1">
                        <h5 class="fs-14 mb-1" data-dz-name>&nbsp;</h5>
                        <p class="fs-13 text-muted mb-0" data-dz-size></p>
                        <strong class="error text-danger" data-dz-errormessage></strong>
                    </div>
                </div>
                <div class="flex-shrink-0 ms-3">
                    @if(!($field->isDisabledForAction($action ?? null)))
                    <div data-dz-remove class="btn btn-sm btn-danger delete_image_{{$field->name . '_' . ($tab_lang ?? '')}}">
                        <i class="{{ nexus_icon('delete') }} align-middle fs-18"></i>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </li>
</ul>
<!-- end dropzon-preview -->


@push('js')
    <script>
        Dropzone.autoDiscover = false;
        console.log('aaaa1')
        jQuery(document).ready(function () {
            console.log('aaaa')
            jQuery('.delete_image_{{$field->name . '_' . ($tab_lang ?? '')}}').click(function () {
                jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}').val('').change();
                jQuery('#dropzone-preview{{$field->name . '_' . ($tab_lang ?? '')}}').addClass('d-none');
            });

            jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}').each(function () {
                var elem = jQuery(this);

                jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}_prev').attr('src', '/' + elem.val().replaceAll('\\', '/'));
                elem.data('oldVal', elem.val().replaceAll('\\', '/'));
                elem.bind("propertychange change click keyup input paste", function (event) {
                    console.log('change detected');
                    if (elem.data('oldVal') !== elem.val().replaceAll('\\', '/')) {
                        elem.data('oldVal', elem.val().replaceAll('\\', '/'));
                        jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}_prev').attr('src', '/' + elem.val().replaceAll('\\', '/'));
                        jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}_link').attr('href', '/' + elem.val().replaceAll('\\', '/'));
                    } else {
                        jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}_prev').attr('src', '/' + elem.val().replaceAll('\\', '/'));
                        jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}_link').attr('href', '/' + elem.val().replaceAll('\\', '/'));
                    }
                    if (elem.val().trim() == '') {
                        jQuery('#dropzone-preview{{$field->name . '_' . ($tab_lang ?? '')}}:not(.d-none)').addClass('d-none');
                    } else {
                        jQuery('#dropzone-preview{{$field->name . '_' . ($tab_lang ?? '')}}.d-none').removeClass('d-none');
                    }
                });
            });
        });
    </script>
@endpush
