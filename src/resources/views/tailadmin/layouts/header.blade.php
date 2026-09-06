<header class="sticky top-0 z-30 flex w-full border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-grow items-center justify-between px-4 py-3 lg:px-6">

        <div class="flex items-center gap-3">
            <button type="button" @click="$store.sidebar.mobileOpen = true"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 lg:hidden dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">
                <i class="{{ nexus_icon('menu') }} text-xl"></i>
            </button>
            <button type="button" @click="$store.sidebar.toggleCollapsed()"
                class="hidden h-10 w-10 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 lg:flex dark:text-gray-400 dark:hover:bg-white/5">
                <i class="{{ nexus_icon('menu_toggle') }} text-xl"></i>
            </button>

            <form id="nexusGlobalSearchForm" autocomplete="off" onsubmit="return false;" class="relative hidden md:block">
                <i class="{{ nexus_icon('search') }} pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="nexusGlobalSearchInput" autocomplete="off"
                    placeholder="{{ __('nexus::translate.search') ?? 'Search...' }}"
                    class="h-10 w-64 rounded-lg border border-gray-200 bg-transparent py-2 pl-9 pr-14 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 xl:w-80 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                <kbd id="nexusGlobalSearchShortcutHint"
                    class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 items-center gap-0.5 rounded border border-gray-200 px-1.5 py-0.5 text-[11px] font-medium text-gray-400 md:inline-flex dark:border-gray-700 dark:text-gray-500">
                    <span id="nexusGlobalSearchShortcutKey">Ctrl</span>K
                </kbd>
                <div id="nexusGlobalSearchResults"
                    class="absolute left-0 top-full z-40 mt-2 hidden max-h-80 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900"></div>
            </form>
        </div>

        @push('js')
            <script>
                (function () {
                    const input = document.getElementById('nexusGlobalSearchInput');
                    const results = document.getElementById('nexusGlobalSearchResults');
                    if (!input || !results) return;

                    let timer = null;

                    input.addEventListener('input', function () {
                        clearTimeout(timer);
                        const term = input.value.trim();
                        if (term.length < 2) {
                            results.classList.add('hidden');
                            results.innerHTML = '';
                            return;
                        }
                        timer = setTimeout(function () {
                            fetch('{{ url(config('nexus.admin_prefix') . '/search') }}?q=' + encodeURIComponent(term), {
                                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                            })
                                .then((r) => r.json())
                                .then((data) => {
                                    const groups = data.results || [];
                                    results.classList.remove('hidden');
                                    if (!groups.length) {
                                        results.innerHTML = '<div class="p-2 text-sm text-gray-400">@lang('nexus::translate.no_search_results')</div>';
                                    } else {
                                        results.innerHTML = groups.map((group) => {
                                            const items = group.items.map((item) =>
                                                '<a class="block rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5" href="' + item.url + '">' + item.label + '</a>'
                                            ).join('');
                                            return '<h6 class="px-2 pt-2 pb-1 text-xs font-semibold uppercase text-gray-400">' + group.label + '</h6>' + items;
                                        }).join('');
                                    }
                                })
                                .catch(() => {});
                        }, 300);
                    });

                    document.addEventListener('click', function (e) {
                        if (!input.contains(e.target) && !results.contains(e.target)) {
                            results.classList.add('hidden');
                        }
                    });

                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') {
                            results.classList.add('hidden');
                            input.blur();
                        }
                    });

                    // Cmd+K on Mac, Ctrl+K everywhere else — jumps to and
                    // focuses global search from anywhere on the page, same
                    // as Filament/Linear/GitHub's command-palette shortcut.
                    // Global (not gated on current focus) is deliberate: the
                    // whole point is "I don't need to click the search box
                    // first". preventDefault() stops some browsers' own
                    // Ctrl+K (focus address bar) from firing alongside it.
                    const shortcutHint = document.getElementById('nexusGlobalSearchShortcutHint');
                    const shortcutKeyLabel = document.getElementById('nexusGlobalSearchShortcutKey');
                    if (shortcutHint) {
                        shortcutHint.classList.remove('hidden');
                        if (/Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)) {
                            shortcutKeyLabel.textContent = '⌘';
                        }
                    }

                    document.addEventListener('keydown', function (e) {
                        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                            e.preventDefault();
                            input.focus();
                            input.select();
                        }
                    });
                })();
            </script>
        @endpush

        <div class="flex items-center gap-1.5">

            <button type="button" @click="$store.theme.toggle()"
                class="flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
                <i class="bx text-xl" :class="$store.theme.theme === 'dark' ? 'bx-sun' : 'bx-moon'"></i>
            </button>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
                    <i class="{{ nexus_icon('notifications') }} text-xl"></i>
                    @php
                        $unreadNotificationsCount = \Illuminate\Support\Facades\Schema::hasTable('notifications')
                            ? auth()?->user()?->unreadNotifications()?->count()
                            : 0;
                    @endphp
                    @if ($unreadNotificationsCount)
                        <span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-error-500 px-1 text-[10px] font-medium text-white">
                            {{ $unreadNotificationsCount }}
                        </span>
                    @endif
                </button>

                <div x-show="open" x-cloak x-transition
                    class="absolute right-0 z-40 mt-2 w-80 rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-2 flex items-center justify-between px-1">
                        <h6 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                            {{ __('nexus::translate.notifications.notification') }}
                        </h6>
                        <a href="javascript:void(0)" id="read-all-link" class="text-xs text-brand-500 hover:text-brand-600">
                            {{ __('nexus::translate.notifications.read_all') }}
                        </a>
                    </div>
                    <div id="notifications-list" class="max-h-72 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800"></div>
                    <div class="mt-2 border-t border-gray-100 pt-2 text-center dark:border-gray-800">
                        <a id="load-more" href="javascript:void(0)" class="text-xs font-medium text-brand-500 hover:text-brand-600">
                            {{ __('nexus::translate.notifications.load_more') }}
                        </a>
                    </div>
                </div>
            </div>

            @push('js')
                <script>
                    (function () {
                        const readAllLink = document.getElementById('read-all-link');
                        readAllLink?.addEventListener('click', function () {
                            fetch('{{ route('nexus.notifications.readAll') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({}),
                            })
                                .then((r) => r.json())
                                .then((data) => {
                                    if (data.status === 'success') {
                                        location.reload();
                                    }
                                });
                        });

                        let currentPage = 1;
                        let totalPages = 0;

                        function loadNotifications(page) {
                            fetch('{{ route('nexus.notifications.index') }}?page=' + page, {
                                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                            })
                                .then((r) => r.json())
                                .then((response) => {
                                    const list = document.getElementById('notifications-list');
                                    const notifications = response.data || [];

                                    if (page === 1 && notifications.length === 0) {
                                        list.innerHTML = '<div class="p-3 text-sm text-gray-400">@lang('nexus::translate.notifications.no_notifications')</div>';
                                    }

                                    notifications.forEach(function (notification) {
                                        const isUnread = !notification.read_at;
                                        const item = document.createElement('div');
                                        item.className = 'flex items-start gap-2 py-2.5 px-1' + (isUnread ? ' bg-brand-50/50 dark:bg-brand-500/10' : '');
                                        item.dataset.id = notification.id;
                                        item.innerHTML = '<div class="flex-1"><p class="text-sm font-medium text-gray-800 dark:text-white/90">' + notification.data.title + '</p>'
                                            + '<p class="text-xs text-gray-500 dark:text-gray-400">' + notification.data.body + '</p></div>'
                                            + (isUnread ? '<button type="button" class="mark-as-read text-brand-500 hover:text-brand-600" data-id="' + notification.id + '"><i class="bx bx-check text-base"></i></button>' : '');
                                        list.appendChild(item);
                                    });

                                    totalPages = response.meta.last_page;
                                    const loadMore = document.getElementById('load-more');
                                    if (loadMore && currentPage >= totalPages) {
                                        loadMore.classList.add('hidden');
                                    }
                                })
                                .catch(() => {});
                        }

                        loadNotifications(currentPage);

                        document.getElementById('load-more')?.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (currentPage < totalPages) {
                                currentPage++;
                                loadNotifications(currentPage);
                            }
                        });

                        document.getElementById('notifications-list')?.addEventListener('click', function (e) {
                            const btn = e.target.closest('.mark-as-read');
                            if (!btn) return;

                            const url = '{{ route('nexus.notifications.markAsRead', ':id') }}'.replace(':id', btn.dataset.id);
                            fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({}),
                            })
                                .then((r) => r.json())
                                .then((data) => {
                                    if (data.success) {
                                        location.reload();
                                    }
                                });
                        });
                    })();
                </script>
            @endpush

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    class="flex items-center gap-2.5 rounded-full p-1 pr-2 hover:bg-gray-100 dark:hover:bg-white/5">
                    <img class="h-9 w-9 rounded-full object-cover" src="{{ asset(auth()->user()?->avatar ?? '') }}" alt="Avatar">
                    <span class="hidden text-sm font-medium text-gray-700 xl:inline-block dark:text-gray-300">
                        {{ auth()->user()?->email ?? 'Unauthenticated' }}
                    </span>
                    <i class="bx bx-chevron-down hidden text-gray-400 xl:inline-block"></i>
                </button>

                <div x-show="open" x-cloak x-transition
                    class="absolute right-0 z-40 mt-2 w-56 rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                    <a href="#" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                        <i class="{{ nexus_icon('profile') }} text-lg"></i>
                        @lang('nexus::translate.Profile')
                    </a>
                    <a href="{{ route('nexus.module.action', ['module' => 'settings', 'action' => 'settings']) }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                        <i class="{{ nexus_icon('tools') }} text-lg"></i>
                        @lang('nexus::translate.Settings')
                    </a>
                    <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>
                    <a href="{{ route('logout') }}" class="logout_action flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10">
                        <i class="{{ nexus_icon('logout') }} text-lg"></i>
                        @lang('nexus::translate.Logout')
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
