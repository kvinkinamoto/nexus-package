@php $icon = ''; @endphp
@foreach($menus as $childMenu)
    {{-- @if($childMenu['parent'] == $menu['parent'])--}}
    {{-- @if(empty($icon))--}}
    {{-- @php $icon = !empty($childMenu['icon']) ? $childMenu['icon'] : ''; @endphp--}}
    {{-- @endif--}}
    {{-- @if(url()->current() == route('admin.get_action', ['action'=>'index','moduleName'=> $childMenu['module']]) )--}}
    {{--
    <?php $menuState = true; ?>--}}
    {{-- @endif--}}
    {{-- @endif--}}
@endforeach


<li>
    <a href="javascript: void(0);" class="has-arrow waves-effect @if($menuState ?? false) active @endif">
        <i class="{{ nexus_icon($menu->icon ?? 'parent_menu') }}"></i>
        <span key="t-{{ $menu->parent }}">
            @if(str_contains($menu->parent, '::'))
                @lang($menu->parent)
            @else
                @lang('nexus::translate.' . $menu->parent)
            @endif
        </span>
    </a>
    <ul class="sub-menu" aria-expanded="false">
        @foreach($menus as $childMenu)
            @if($childMenu->parent == $menu->parent)
                @include('nexus::' . config('nexus.template') . '.templates.menu', ['menu' => $childMenu, 'is_child' => true])
            @endif
        @endforeach
    </ul>
</li>