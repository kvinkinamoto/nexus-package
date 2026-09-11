@if(isset($menu))
    @php
        $itemUrl = route('nexus.module.action', ['action' => 'index', 'module' => $menu->module]);
        $isActive = url()->current() == $itemUrl;
    @endphp
    <li>
        <a href="{{ $itemUrl }}"
            class="menu-item {{ $isActive ? 'menu-item-active' : 'menu-item-inactive' }} {{ $is_child ? 'pl-9' : '' }}">
            {!! nexus_icon_html($menu->icon, $menu->module, 'default_icon', 'menu-item-icon ' . ($isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive') . ' text-lg') !!}

            <span class="menu-item-text" :class="$store.sidebar.collapsed ? 'lg:hidden' : ''">
                @if(!empty($menu->label))
                    @if(!str_contains($menu->label, '::'))
                        @lang($menu->label)
                    @else
                        @lang(Str::lcfirst($menu->name) . '::translate.' . $menu->label)
                    @endif
                @else
                    @lang(Str::lcfirst($menu->name) . '::translate.' . $menu->name)
                @endif
            </span>
        </a>
    </li>
@endif
