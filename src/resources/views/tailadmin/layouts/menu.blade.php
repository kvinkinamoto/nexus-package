<aside
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-gray-200 bg-white transition-transform duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 dark:border-gray-800 dark:bg-gray-900"
    :class="{
        '-translate-x-full': !$store.sidebar.mobileOpen,
        'translate-x-0': $store.sidebar.mobileOpen,
        'lg:w-[90px]': $store.sidebar.collapsed,
    }">

    <div class="flex h-[72px] shrink-0 items-center px-6" :class="$store.sidebar.collapsed ? 'lg:justify-center lg:px-0' : ''">
        <a href="{{ route('nexus.admin') }}" class="flex items-center gap-2">
            <img src="{{ asset('nexus/images/logo-sm.svg') }}" alt="" class="h-8 w-8">
            <span class="text-lg font-semibold text-gray-800 dark:text-white/90" :class="$store.sidebar.collapsed ? 'lg:hidden' : ''">
                Nexus
            </span>
        </a>
    </div>

    <nav class="custom-scrollbar flex-1 overflow-y-auto px-4 pb-6">
        <p class="mb-2 px-1 text-xs font-medium uppercase tracking-wide text-gray-400" :class="$store.sidebar.collapsed ? 'lg:hidden' : ''">
            @lang('nexus::translate.general')
        </p>

        <ul class="flex flex-col gap-1">
            @php $renderend = []; @endphp
            @foreach($menus ?? [] as $menu)
                @if(!$menu->parent)
                    @include('nexus::'. config('nexus.template').'.templates.menu', ['menu' => $menu, 'is_child' => false])
                @elseif(!in_array($menu->parent, $renderend))
                    @php $renderend[] = $menu->parent; @endphp
                    @include('nexus::'. config('nexus.template').'.templates.parent_menu', ['menu' => $menu, 'menus' => $menus])
                @endif
            @endforeach
        </ul>
    </nav>
</aside>
