<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin</title>

    {{--
        Minimal shell for slide-over panels: the real edit/create page is
        loaded here (via ?panel=1) inside an <iframe>, so it needs the same
        field-widget assets as layouts/adminpanel.blade.php (CKEditor,
        Dropzone, Choices.js) but none of its chrome (header/sidebar/footer)
        — deliberately duplicated rather than extracted into a shared
        partial, same rationale as infolistSection.blade.php: this list
        only changes when adminpanel's own asset list does, and keeping it
        a flat file makes that diff obvious instead of hidden behind an
        include.
    --}}

    <script>
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link rel="stylesheet" href="{{ asset('nexus/css/icons.min.css') }}" type="text/css">
    {{-- Renders "solar:xxx-bold" section icons — see adminpanel.blade.php's comment. --}}
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@2.1.0/dist/iconify-icon.min.js"></script>
    {{-- app.css must load AFTER choices.min.css — see layouts/adminpanel.blade.php's comment. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@11.2.4/public/assets/styles/choices.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('head')
    @yield('css')
    @stack('css')

    @livewireStyles
</head>

<body class="font-outfit bg-gray-50 p-5 text-gray-800 dark:bg-gray-900 dark:text-white/90" x-data>

    @include('nexus::' . config('nexus.template') . '.components.impersonation_banner')

    <div class="mx-auto max-w-(--breakpoint-2xl)">
        @yield('mainContent')
    </div>

    @if(session('alert_message') && config('nexus.toast.enabled'))
        @include('nexus::'. config('nexus.template'). '.components.toast', ['message' => session('alert_message'), 'type' => session('alert_type')])
    @endif

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

    <script src="{{ asset('packages/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/dropzone/dropzone.js') }}"></script>

    @yield('js')
    @stack('js')

    <script src="https://cdn.jsdelivr.net/npm/choices.js@11.2.4/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-choices]').forEach(function (item) {
                new Choices(item, {});
            });

            // Workaround for a stuck-render quirk — see adminpanel.blade.php's
            // matching comment for the full explanation.
            if (window.customElements?.get('iconify-icon')) {
                document.querySelectorAll('iconify-icon[icon]').forEach(function (el) {
                    var icon = el.getAttribute('icon');
                    el.removeAttribute('icon');
                    requestAnimationFrame(function () { el.setAttribute('icon', icon); });
                });
            }
        });
    </script>

    @livewireScripts
</body>

</html>
