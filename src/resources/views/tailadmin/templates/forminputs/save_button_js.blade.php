<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('#{{$form_id}}');
        ['save_btn', 'save_and_close_btn', 'save_and_new'].forEach(function (id) {
            const btn = document.getElementById(id);
            btn?.addEventListener('click', function (e) {
                if (form.checkValidity()) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'save';
                    hidden.value = btn.value;
                    form.appendChild(hidden);
                    form.submit();
                } else {
                    form.reportValidity();
                }
                e.preventDefault();
            });
        });
    });
</script>
