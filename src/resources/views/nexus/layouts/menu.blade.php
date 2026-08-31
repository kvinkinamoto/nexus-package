<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-base">
                    @lang('nexus::translate.general')
                </li>

                @php $renderend = []; @endphp
                @foreach($menus ?? [] as $menu)
                    @if(!$menu->parent)
                        @include('nexus::'. config('nexus.template').'.templates.menu',['menu' => $menu, 'is_child' => false])
                    @elseif(!in_array($menu->parent, $renderend))
                        @php $renderend[] = $menu->parent; @endphp
                        @include('nexus::'. config('nexus.template').'.templates.parent_menu',['menu' => $menu, 'menus' => $menus])
                    @endif
                @endforeach

{{--                <li class="menu-title" key="t-general">--}}
{{--                    @lang('nexus::translate.general')--}}
{{--                </li>--}}

{{--                <li class="menu-title" key="t-system">--}}
{{--                    @lang('nexus::translate.system')--}}
{{--                </li>--}}

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
