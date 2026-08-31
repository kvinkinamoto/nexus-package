<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{--
        Minimal shell for slide-over panels: the real edit/create page is
        loaded here (via ?panel=1) inside an <iframe>, so it needs the same
        field-widget assets as layouts/adminpanel.blade.php (CKEditor,
        Dropzone, Choices.js, colorbox, app.js) but none of its chrome
        (header/sidebar/footer/global modals) — deliberately duplicated
        rather than extracted into a shared partial, same rationale as
        infolistSection.blade.php: this list only changes when adminpanel's
        own asset list does, and keeping it a flat file makes that diff
        obvious instead of hidden behind an include.
    --}}

    <link rel="stylesheet" href="{{asset('packages/jquery-colorbox/example1/colorbox.css')}}">
    <link rel="stylesheet" href="{{asset('nexus/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css')}}" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('nexus/css/bootstrap.min.css')}}" type="text/css" id="bootstrap-style">
    <link rel="stylesheet" href="{{asset('nexus/css/icons.min.css')}}" type="text/css">
    <link id="app-style" rel="stylesheet" href="{{asset('nexus/css/app.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('nexus/css/nexus-theme.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('nexus/libs/magnific-popup/magnific-popup.css')}}" type="text/css">
    <script src="{{asset('packages/jquery/jquery-3.7.1.min.js')}}"></script>
    <script src="{{asset('nexus/libs/magnific-popup/jquery.magnific-popup.min.js')}}"></script>

    @yield('head')
    @yield('css')
    @stack('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        body {
            background: var(--bs-body-bg, #f5f6f8);
            padding: 1.25rem;
        }
        .image-popup { cursor: zoom-in !important; }
        .mfp-bg { z-index: 10501 !important; }
        .mfp-wrap { z-index: 10502 !important; }
    </style>
    @livewireStyles
</head>

<body>

    @include('nexus::' . config('nexus.template') . '.components.impersonation_banner')

    <div class="container-fluid">
        @yield('mainContent')
    </div>

    @if(session('alert_message') && config('nexus.toast.enabled'))
        @include('nexus::'. config('nexus.template'). '.components.toast', ['message' => session('alert_message'), 'type' => session('alert_type')])
    @endif

    <script src="{{asset('nexus/libs/bootstrap/js/bootstrap.min.js')}}"></script>
    <script src="{{asset('nexus/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('packages/jquery-colorbox/jquery.colorbox.js')}}"></script>
    <script src="{{asset('packages/barryvdh/elfinder/js/standalonepopup.js')}}"></script>
    <script src="{{asset('packages/ckeditor/ckeditor.js')}}"></script>
    <script src="{{asset('packages/ckeditor/adapters/jquery.js')}}"></script>

    <script src="{{asset('nexus/js/app.js')}}"></script>
    <script src="{{asset('adminlte/plugins/dropzone/dropzone.js')}}"></script>

    <script>
        jQuery(document).ready(function () {
            if (typeof jQuery.fn.magnificPopup === 'function') {
                jQuery('body').magnificPopup({
                    delegate: '.image-popup',
                    type: 'image',
                    closeOnContentClick: true,
                    mainClass: 'mfp-img-mobile',
                    image: {
                        verticalFit: true
                    }
                });
            }
        });
    </script>

    @if(session('alert_message') && config('nexus.toast.enabled'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var toastEl = document.getElementById('liveToast');
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
            });
        </script>
    @endif

    @yield('js')
    @stack('js')

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var choicesExamples = document.querySelectorAll("[data-choices]");
            choicesExamples.forEach(function (item) {
                var choiceData = {};
                var isChoicesVal = item.attributes;
                if (isChoicesVal["data-choices-groups"]) {
                    choiceData.placeholderValue = "This is a placeholder set in the config";
                }
                if (isChoicesVal["data-choices-search-false"]) {
                    choiceData.searchEnabled = false;
                }
                if (isChoicesVal["data-choices-search-true"]) {
                    choiceData.searchEnabled = true;
                }
                if (isChoicesVal["data-choices-removeItem"]) {
                    choiceData.removeItemButton = true;
                }
                if (isChoicesVal["data-choices-sorting-false"]) {
                    choiceData.shouldSort = false;
                }
                if (isChoicesVal["data-choices-sorting-true"]) {
                    choiceData.shouldSort = true;
                }
                if (isChoicesVal["data-choices-multiple-remove"]) {
                    choiceData.removeItemButton = true;
                }
                if (isChoicesVal["data-choices-limit"]) {
                    choiceData.maxItemCount = isChoicesVal["data-choices-limit"].value.toString();
                }
                if (isChoicesVal["data-choices-editItem-true"]) {
                    choiceData.maxItemCount = true;
                }
                if (isChoicesVal["data-choices-editItem-false"]) {
                    choiceData.maxItemCount = false;
                }
                if (isChoicesVal["data-choices-text-unique-true"]) {
                    choiceData.duplicateItemsAllowed = false;
                    choiceData.paste = false;
                }
                if (isChoicesVal["data-choices-text-disabled-true"]) {
                    choiceData.addItems = false;
                }
                isChoicesVal["data-choices-text-disabled-true"] ? new Choices(item, choiceData).disable() : new Choices(item, choiceData);
            });
        });
    </script>

    @livewireScripts
</body>

</html>
