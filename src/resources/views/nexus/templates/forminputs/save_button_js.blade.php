<script>
    {{--jQuery(document).ready(function () {--}}
    {{--    jQuery('#save_btn, #save_and_close_btn, #save_and_new').click(function (value) {--}}
    {{--        jQuery("<input />").attr("type", "hidden")--}}
    {{--            .attr("name", "save")--}}
    {{--            .attr("value", jQuery(this).val())--}}
    {{--            .appendTo("#{{$form_id}}");--}}
    {{--        jQuery("#{{$form_id}}").submit();--}}
    {{--        return false;--}}
    {{--    });--}}
    {{--});--}}

    jQuery(document).ready(function () {
        jQuery('#save_btn, #save_and_close_btn, #save_and_new').click(function (value) {
            var form = document.querySelector("#{{$form_id}}");
            if (form.checkValidity()) {
                jQuery("<input />").attr("type", "hidden")
                    .attr("name", "save")
                    .attr("value", jQuery(this).val())
                    .appendTo("#{{$form_id}}");
                jQuery("#{{$form_id}}").submit();
            } else {
                // Відобразити повідомлення про помилки валідації
                form.reportValidity();
            }
            return false;
        });
    });
</script>
