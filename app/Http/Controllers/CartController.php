<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        return view('cart.index', [
            'items' => $cart->items(),
            'total' => $cart->total(),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        abort_unless($product->is_active, 404);

        $cart->add($product->id, $validated['quantity'] ?? 1);

        return back()->with('status', __(':name added to your basket.', ['name' => $product->name]));
    }

    public function update(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart->update($product->id, $validated['quantity']);

        return back()->with('status', __('Basket updated.'));
    }

    public function destroy(Product $product, Cart $cart): RedirectResponse
    {
        $cart->remove($product->id);

        return back()->with('status', __('Item removed from your basket.'));
    }
}
