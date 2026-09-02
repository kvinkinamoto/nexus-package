<input type="hidden" name="fieldName" value="{{$fieldName}}">
<label class="relative inline-flex cursor-pointer items-center">
    <input name="{{$fieldName}}-toggle"
           class="peer sr-only"
           type="checkbox"
           id="{{$fieldName}}_toggle_{{ $item->id }}"
           onchange="this.form.requestSubmit()"
           @if($item->{$fieldName} == 1) checked @endif>
    <span class="h-5.5 w-10 rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700"></span>
    <span class="absolute left-0.5 top-0.5 h-4.5 w-4.5 rounded-full bg-white transition peer-checked:translate-x-4.5"></span>
</label>
