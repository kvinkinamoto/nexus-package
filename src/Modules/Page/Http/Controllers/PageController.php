<?php

namespace Nodex\Nexus\Modules\Page\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Nodex\Nexus\Modules\Page\Models\Page;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('page::public.page', ['page' => $page]);
    }
}
