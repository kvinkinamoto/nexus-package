@if (session('messages'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h5><i class="icon fas fa-sun"></i>@lang('Success')</h5>
        <ul>
        @foreach (session('messages') as $item)
                <li>{{ $item }}</li>
        @endforeach
        </ul>
    </div>
@endif

