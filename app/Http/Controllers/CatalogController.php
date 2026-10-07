<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category')->trim()->value();

        $categories = TemplateCategory::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        $templates = Template::query()
            ->with(['category', 'demoInvitation'])
            ->active()
            ->when($category !== '', fn ($query) => $query->whereHas(
                'category',
                fn ($categoryQuery) => $categoryQuery->where('slug', $category)->where('is_active', true),
            ))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('catalog.index', [
            'categories' => $categories,
            'templates' => $templates,
            'activeCategory' => $category,
            'whatsappUrl' => 'https://wa.me/'.config('invitation.brand.whatsapp'),
        ]);
    }
}
