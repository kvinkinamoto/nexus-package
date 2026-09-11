<li x-data="{ open: false }">
    <button type="button" @click="open = !open"
        class="menu-item menu-item-inactive w-full">
        {!! nexus_icon_html($menu->icon ?? null, null, 'parent_menu', 'menu-item-icon menu-item-icon-inactive text-lg') !!}

        <span class="menu-item-text flex-1 text-left" :class="$store.sidebar.collapsed ? 'lg:hidden' : ''">
            @if(str_contains($menu->parent, '::'))
                @lang($menu->parent)
            @else
                @lang('nexus::translate.' . $menu->parent)
            @endif
        </span>

        <i class="bx bx-chevron-down menu-item-arrow menu-item-arrow-inactive text-base transition-transform duration-200"
            :class="{ 'rotate-180': open }" :style="$store.sidebar.collapsed ? 'display:none' : ''"></i>
    </button>

    <ul x-show="open" x-collapse x-cloak class="mt-1 flex flex-col gap-1" :class="$store.sidebar.collapsed ? 'lg:hidden' : ''">
        @foreach($menus as $childMenu)
            @if($childMenu->parent == $menu->parent)
                @include('nexus::' . config('nexus.template') . '.templates.menu', ['menu' => $childMenu, 'is_child' => true])
            @endif
        @endforeach
    </ul>
</li>
