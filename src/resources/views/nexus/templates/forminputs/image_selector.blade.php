<div class="form-group">
    <label for="feature_image">{{$lableCaption??__('nexus::translate.image')}}</label>
    <div class="input-group mb-3">
        <?php
        if (!isset($value)){
            $value = '';
        }
        ?>

        <a href="/{{old($imageFieldId??'img')??($value??'')}}" id="{{$imageFieldId??'img'}}_link" class="image-popup d-block mb-3">
            <img src="/{{old($imageFieldId??'img')??($value??'')}}" id="{{$imageFieldId??'img'}}_prev"
                 style="max-height: 200px; max-width: 100%; object-fit: contain; display: block;">
        </a>
        <div class="input-group-prepend">
            <button data-inputid="{{$imageFieldId??'img'}}" type="button" class="btn btn-primary popup_selector">@lang('nexus::translate.choose')</button>
        </div>
        <input type="text" id="{{$imageFieldId??'img'}}" name="{{$imageFieldName??$imageFieldId??'img'}}"
               value="{{old($imageFieldId??'img')??($value??'')}}"
               class="form-control @error($imageFieldId??'img') is-invalid @enderror">
        {!! $errors->first($imageFieldId??'img', '<small class="error invalid-feedback">:message</small>') !!}
    </div>
</div>
