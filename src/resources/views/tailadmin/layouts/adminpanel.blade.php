<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin</title>

    <script>
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link rel="stylesheet" href="{{ asset('nexus/css/icons.min.css') }}" type="text/css">
    {{--
        Renders every #[Module]/#[Section] "solar:xxx-bold" icon (an Iconify
        icon-set:name identifier, not a CSS class — see nexus_icon_html()) via
        the <iconify-icon> custom element it defines.
    --}}
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@2.1.0/dist/iconify-icon.min.js"></script>
    {{--
        Choices.js's own CSS is a light-only theme with no dark-mode
        awareness; app.css carries dark-theme overrides for its classes
        (see the "Choices.js dark-theme overrides" section), so it must load
        AFTER this link — same specificity, last one in the cascade wins —
        rather than needing !important everywhere.
    --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@11.2.4/public/assets/styles/choices.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('head')
    @yield('css')
    @stack('css')

    @livewireStyles
</head>

<body class="font-outfit bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-white/90" x-data>

    @include('nexus::' . config('nexus.template') . '.components.impersonation_banner')

    <div class="flex h-screen overflow-hidden">

        @include('nexus::' . config('nexus.template') . '.layouts.menu')

        <!-- Mobile sidebar backdrop -->
        <div x-show="$store.sidebar.mobileOpen" x-cloak @click="$store.sidebar.toggleMobile()"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"></div>

        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">

            @include('nexus::' . config('nexus.template') . '.layouts.header')

            @include('nexus::partials.module-dependency-warning')

            @include('nexus::' . config('nexus.template') . '.layouts.content')

        </div>
    </div>

    @if(session('alert_message') && config('nexus.toast.enabled'))
        @include('nexus::'. config('nexus.template'). '.components.toast', ['message' => session('alert_message'), 'type' => session('alert_type')])
    @endif

    {{--
        elFinder file/image picker popup (vanilla JS, no jQuery/Colorbox —
        replaces the old jquery-colorbox modal). Any button with class
        "popup_selector" and data-inputid="{fieldId}" opens
        /elfinder/popup/{fieldId} in this iframe; elFinder's own inner page
        calls window.parent.processSelectedFile(path, fieldId) to hand the
        chosen path back — that contract is unchanged, only the modal chrome
        around it is new.
    --}}
    <div id="nexusElfinderModal" class="fixed inset-0 z-999999 hidden">
        <div class="absolute inset-0 bg-gray-900/50" onclick="closeElfinderPopup()"></div>
        <div class="absolute inset-6 overflow-hidden rounded-2xl bg-white shadow-theme-xl sm:inset-10 dark:bg-gray-900">
            <button type="button" onclick="closeElfinderPopup()"
                class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white text-gray-500 shadow-theme-sm hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <i class="bx bx-x"></i>
            </button>
            <iframe id="nexusElfinderFrame" src="about:blank" class="h-full w-full border-0"></iframe>
        </div>
    </div>
    <script>
        function openElfinderPopup(inputId) {
            document.getElementById('nexusElfinderFrame').src = '/elfinder/popup/' + inputId;
            document.getElementById('nexusElfinderModal').classList.remove('hidden');
        }
        function closeElfinderPopup() {
            document.getElementById('nexusElfinderModal').classList.add('hidden');
            document.getElementById('nexusElfinderFrame').src = 'about:blank';
        }
        function processSelectedFile(filePath, requestingField) {
            const el = document.getElementById(requestingField);
            if (el) {
                el.value = filePath;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
            closeElfinderPopup();
        }
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.popup_selector');
            if (!btn) return;
            e.preventDefault();
            openElfinderPopup(btn.dataset.inputid);
        });
    </script>

    {{-- CKEditor core (no jQuery adapter) — only invoked from field types with isEditor => true --}}
    <script src="{{ asset('packages/ckeditor/ckeditor.js') }}"></script>
    {{-- Dropzone (vanilla JS, no jQuery) — used by the image field type's drag-and-drop upload --}}
    <script src="{{ asset('packages/dropzone/dropzone.js') }}"></script>

    @yield('js')
    @stack('js')

    <script src="https://cdn.jsdelivr.net/npm/choices.js@11.2.4/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-choices]').forEach(function (item) {
                var choiceData = {};
                var attrs = item.attributes;
                if (attrs['data-choices-groups']) choiceData.placeholderValue = 'This is a placeholder set in the config';
                if (attrs['data-choices-search-false']) choiceData.searchEnabled = false;
                if (attrs['data-choices-search-true']) choiceData.searchEnabled = true;
                if (attrs['data-choices-removeItem']) choiceData.removeItemButton = true;
                if (attrs['data-choices-sorting-false']) choiceData.shouldSort = false;
                if (attrs['data-choices-sorting-true']) choiceData.shouldSort = true;
                if (attrs['data-choices-multiple-remove']) choiceData.removeItemButton = true;
                if (attrs['data-choices-limit']) choiceData.maxItemCount = attrs['data-choices-limit'].value.toString();
                if (attrs['data-choices-editItem-true']) choiceData.maxItemCount = true;
                if (attrs['data-choices-editItem-false']) choiceData.maxItemCount = false;
                if (attrs['data-choices-text-unique-true']) {
                    choiceData.duplicateItemsAllowed = false;
                    choiceData.paste = false;
                }
                if (attrs['data-choices-text-disabled-true']) choiceData.addItems = false;

                attrs['data-choices-text-disabled-true']
                    ? new Choices(item, choiceData).disable()
                    : new Choices(item, choiceData);
            });

            document.querySelectorAll('.logout_action').forEach(function (el) {
                el.addEventListener('click', function () {
                    document.getElementById('logoutForm')?.submit();
                });
            });

            // iconify-icon@2.1.0 never renders elements already present in
            // the server-rendered HTML at DOMContentLoaded time — only ones
            // created/attribute-changed afterwards actually trigger its
            // internal fetch (reproduced directly: a fresh element resolves
            // immediately, but toggling icon="" off/on on a "stuck" one also
            // makes it resolve, while just waiting never does). Kicking
            // every one once here is the workaround.
            if (window.customElements?.get('iconify-icon')) {
                document.querySelectorAll('iconify-icon[icon]').forEach(function (el) {
                    var icon = el.getAttribute('icon');
                    el.removeAttribute('icon');
                    requestAnimationFrame(function () { el.setAttribute('icon', icon); });
                });
            }
        });
    </script>
    <form method="POST" id="logoutForm" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>

    @livewireScripts
</body>

</html>
