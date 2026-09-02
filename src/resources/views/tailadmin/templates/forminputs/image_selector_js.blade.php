<script>
    document.addEventListener('DOMContentLoaded', function () {
        const elem = document.getElementById('{{$imageFieldId??"img"}}');
        const prev = document.getElementById('{{$imageFieldId??"img"}}_prev');
        const link = document.getElementById('{{$imageFieldId??"img"}}_link');
        if (!elem) return;

        function sync() {
            const newVal = '/' + elem.value.replaceAll('\\', '/');
            prev?.setAttribute('src', newVal);
            link?.setAttribute('href', newVal);
        }

        sync();
        elem.addEventListener('input', sync);
        elem.addEventListener('change', sync);
    });
</script>
