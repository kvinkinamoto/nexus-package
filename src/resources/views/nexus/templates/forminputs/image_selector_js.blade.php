<script>
    jQuery(document).ready(function () {
        jQuery('#{{$imageFieldId??"img"}}').each(function() {
            var elem = jQuery(this);

            jQuery('#{{$imageFieldId??"img"}}_prev').attr('src','/'+elem.val().replaceAll('\\', '/'));
            jQuery('#{{$imageFieldId??"img"}}_link').attr('href','/'+elem.val().replaceAll('\\', '/'));
            elem.data('oldVal', elem.val().replaceAll('\\', '/'));
            elem.bind("propertychange change click keyup input paste", function(event){
                var newVal = '/'+elem.val().replaceAll('\\', '/');
                if (elem.data('oldVal') !== elem.val().replaceAll('\\', '/')) {
                    elem.data('oldVal', elem.val().replaceAll('\\', '/'));
                }
                jQuery('#{{$imageFieldId??"img"}}_prev').attr('src', newVal);
                jQuery('#{{$imageFieldId??"img"}}_link').attr('href', newVal);
            });
        });
    });
</script>
