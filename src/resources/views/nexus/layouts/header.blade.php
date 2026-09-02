<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">

                <a href="{{ route('nexus.admin') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ asset('nexus/images/logo-sm.svg') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('nexus/images/logo-dark.svg') }}" alt="" height="20">
                    </span>
                </a>

                <a href="{{ route('nexus.admin') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('nexus/images/logo-sm.svg') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('nexus/images/logo-light.svg') }}" alt="" height="20">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
{{--
            <div class="dropdown dropdown-mega d-none d-lg-block ms-2">
                <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown"
                        aria-haspopup="false" aria-expanded="false">
                    <span key="t-megamenu">
                        @lang('nexus::translate.Shops')
                    </span>
                    <i class="mdi mdi-chevron-down"></i>
                </button>
                <div class="dropdown-menu dropdown-megamenu col-sm-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="font-size-14" key="t-ui-components">WEB</h5>
                            <ul class="list-unstyled megamenu-list">
                                <li>
                                    <a href="javascript:void(0);" key="t-lightbox">Lightbox</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-range-slider">Range Slider</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-sweet-alert">Sweet Alert</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-rating">Rating</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-forms">Forms</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-tables">Tables</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-charts">Charts</a>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5 class="font-size-14" key="t-applications">API</h5>
                            <ul class="list-unstyled megamenu-list">
                                <li>
                                    <a href="javascript:void(0);" key="t-ecommerce">Ecommerce</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-calendar">Calendar</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-email">Email</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-projects">Projects</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-tasks">Tasks</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" key="t-contacts">Contacts</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            --}}
        </div>

        <div class="d-flex">

            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                     aria-labelledby="page-header-search-dropdown">

                    <form class="p-3" id="nexusGlobalSearchForm" autocomplete="off" onsubmit="return false;">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search ..."
                                       aria-label="Recipient's username" id="nexusGlobalSearchInput" autocomplete="off">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="nexusGlobalSearchResults" style="max-height: 50vh; overflow-y: auto;"></div>
                    </form>
                </div>
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
                                        if (!groups.length) {
                                            results.innerHTML = '<div class="p-2 text-muted">@lang('nexus::translate.no_search_results')</div>';
                                        } else {
                                            results.innerHTML = groups.map((group) => {
                                                const items = group.items.map((item) =>
                                                    '<a class="dropdown-item" href="' + item.url + '">' + item.label + '</a>'
                                                ).join('');
                                                return '<h6 class="dropdown-header">' + group.label + '</h6>' + items;
                                            }).join('');
                                        }
                                    })
                                    .catch(() => {});
                            }, 300);
                        });
                    })();
                </script>
            @endpush

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                    <img id="header-lang-img" src="{{ asset('nexus/images/flags/us.jpg') }}" alt="Header Language"
                         height="16">
                </button>
                <div class="dropdown-menu dropdown-menu-end">

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item language" data-lang="en">
                        <img src="{{ asset('nexus/images/flags/us.jpg') }}" alt="user-image" class="me-1" height="12">
                        <span
                            class="align-middle">English</span>
                    </a>
                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item language" data-lang="sp">
                        <img src="{{ asset('nexus/images/flags/spain.jpg') }}" alt="user-image" class="me-1"
                             height="12"> <span
                            class="align-middle">Spanish</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item language" data-lang="gr">
                        <img src="{{ asset('nexus/images/flags/germany.jpg') }}" alt="user-image" class="me-1"
                             height="12"> <span
                            class="align-middle">German</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item language" data-lang="it">
                        <img src="{{ asset('nexus/images/flags/italy.jpg') }}" alt="user-image" class="me-1"
                             height="12"> <span
                            class="align-middle">Italian</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item language" data-lang="ru">
                        <img src="{{ asset('nexus/images/flags/russia.jpg') }}" alt="user-image" class="me-1"
                             height="12"> <span
                            class="align-middle">Russian</span>
                    </a>
                </div>
            </div>

            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                    <i class="{{ nexus_icon('apps') }}"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <div class="px-lg-2">
                        <div class="row g-0">
                            <div class="col">
                                <a class="dropdown-icon-item" href="#">
                                    <img src="{{ asset('nexus/images/brands/github.png') }}" alt="Github">
                                    <span>GitHub</span>
                                </a>
                            </div>
                            <div class="col">
                                <a class="dropdown-icon-item" href="#">
                                    <img src="{{ asset('nexus/images/brands/bitbucket.png') }}" alt="bitbucket">
                                    <span>Bitbucket</span>
                                </a>
                            </div>
                            <div class="col">
                                <a class="dropdown-icon-item" href="#">
                                    <img src="{{ asset('nexus/images/brands/dribbble.png') }}" alt="dribbble">
                                    <span>Dribbble</span>
                                </a>
                            </div>
                        </div>

                        <div class="row g-0">
                            <div class="col">
                                <a class="dropdown-icon-item" href="#">
                                    <img src="{{ asset('nexus/images/brands/dropbox.png') }}" alt="dropbox">
                                    <span>Dropbox</span>
                                </a>
                            </div>
                            <div class="col">
                                <a class="dropdown-icon-item" href="#">
                                    <img src="{{ asset('nexus/images/brands/mail_chimp.png') }}" alt="mail_chimp">
                                    <span>Mail Chimp</span>
                                </a>
                            </div>
                            <div class="col">
                                <a class="dropdown-icon-item" href="#">
                                    <img src="{{ asset('nexus/images/brands/slack.png') }}" alt="slack">
                                    <span>Slack</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-bs-toggle="fullscreen">
                    <i class="{{ nexus_icon('fullscreen') }}"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect"
                        id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                    {{--
                        Guarded on Schema::hasTable() rather than assuming it's
                        there: this is Laravel's own notifications table, not
                        one of nexus-migrations' own tables, so a fresh install
                        that skipped `nexus:install` (which now runs
                        notifications:table + migrate for exactly this reason)
                        must not 500 the whole admin layout over an unread-count
                        badge — it degrades to "no badge" instead.
                    --}}
                    @php
                        $unreadNotificationsCount = \Illuminate\Support\Facades\Schema::hasTable('notifications')
                            ? auth()?->user()?->unreadNotifications()?->count()
                            : 0;
                    @endphp
                    <i class="{{ nexus_icon('notifications') }}"></i>
                    @if ($unreadNotificationsCount)
                        <span
                            class="badge bg-danger rounded-pill">
                                {{ $unreadNotificationsCount }}
                        </span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                     aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0" key="t-notifications">
                                    {{ __('nexus::translate.notifications.notification') }}
                                </h6>
                            </div>
                            <div class="col-auto">
                                <a href="#!" class="small" key="t-view-all" id="read-all-link">
                                    {{ __('nexus::translate.notifications.read_all') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div id="notifications-list" data-simplebar="init" style="max-height: 230px;">

                    </div>
                    <div class="p-2 border-top d-grid">
                        <a id="load-more" class="btn btn-sm btn-link font-size-14 text-center"
                           href="javascript:void(0)">
                            <i class="mdi mdi-arrow-right-circle me-1"></i>
                            <span key="t-view-more">{{ __('nexus::translate.notifications.load_more') }}</span>
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
                                        list.innerHTML = '<div class="p-3 text-muted">@lang('nexus::translate.notifications.no_notifications')</div>';
                                    }

                                    notifications.forEach(function (notification) {
                                        const isUnread = !notification.read_at;
                                        const item = document.createElement('div');
                                        item.className = 'dropdown-item py-3 border-bottom' + (isUnread ? ' bg-light' : '');
                                        item.dataset.id = notification.id;
                                        item.innerHTML = '<div class="d-flex"><div class="flex-grow-1">'
                                            + '<p class="mb-0 fw-semibold">' + notification.data.title + '</p>'
                                            + '<p class="mb-0 text-wrap">' + notification.data.body + '</p>'
                                            + '</div>'
                                            + (isUnread ? '<div class="ms-3"><i class="mdi mdi-check-all mark-as-read" data-id="' + notification.id + '"></i></div>' : '')
                                            + '</div>';
                                        list.appendChild(item);
                                    });

                                    totalPages = response.meta.last_page;
                                    const loadMore = document.getElementById('load-more');
                                    if (loadMore && currentPage >= totalPages) {
                                        loadMore.style.display = 'none';
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

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="{{ asset(auth()->user()?->avatar ?? '') }}"
                         alt="Header Avatar">
                    <span class="d-none d-xl-inline-block ms-1" key="t-henry">
                        {{ auth()->user()?->email ?? 'Unauthenticated' }}
                    </span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <!-- item-->
                    <a class="dropdown-item" href="#">
                        <i class="{{ nexus_icon('profile') }} font-size-16 align-middle me-1"></i>
                        <span key="t-profile">
                            @lang('nexus::translate.Profile')
                        </span>
                    </a>
                    <a class="dropdown-item d-block" href="#">
                        <span class="badge bg-success float-end">11</span>
                        <i class="{{ nexus_icon('tools') }} font-size-16 align-middle me-1"></i>
                        <span key="t-settings">
                            @lang('nexus::translate.Settings')
                        </span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger logout_action" href="{{route('logout')}}">
                        <i class="{{ nexus_icon('logout') }} font-size-16 align-middle me-1 text-danger"></i>
                        <span key="t-logout">
                            @lang('nexus::translate.Logout')
                        </span>
                    </a>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon right-bar-toggle waves-effect">
                    <i class="{{ nexus_icon('settings') }}"></i>
                </button>
            </div>

        </div>
    </div>
</header>
