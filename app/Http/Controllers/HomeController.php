<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'featuredProducts' => Product::query()->active()->latest()->take(8)->get(),
            'categories' => Product::query()->active()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }
}
