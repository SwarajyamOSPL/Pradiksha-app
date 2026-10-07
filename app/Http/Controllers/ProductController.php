<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category')->value() ?: null;

        return view('products.index', [
            'products' => Product::query()
                ->active()
                ->when($category, fn ($query) => $query->where('category', $category))
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
            'categories' => Product::query()->active()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'selectedCategory' => $category,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('products.show', [
            'product' => $product,
        ]);
    }
}
