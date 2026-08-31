@if(isset($menu))
    <li>
        <a href="{{ route('nexus.module.action', ['action' => 'index', 'module' => $menu->module])}}"
            class="waves-effect @if($is_child) sub-nav-link @else nav-link @endif @if(url()->current() == route('nexus.module.action', ['action' => 'index', 'module' => $menu->module])) active @endif">


            @if(!$is_child)
                <i class="{{ nexus_icon($menu->icon, $menu->module, 'default_icon') }}"></i>
            @endif

            <span key="t-chat">
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