<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use App\Support\StockCheck;
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
            'hasUnpricedItems' => $cart->hasUnpricedItems(),
            'stockShortages' => StockCheck::shortages($cart->items())->keyBy(fn (array $shortage) => $shortage['product']->id),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        abort_unless($product->is_active, 404);

        if (! $product->isInStock()) {
            return back()->withErrors(['quantity' => __(':name is out of stock.', ['name' => $product->name])]);
        }

        $wanted = ($cart->raw()[$product->id] ?? 0) + ($validated['quantity'] ?? 1);

        if ($wanted > $product->stock) {
            $cart->update($product->id, $product->stock);

            return back()->with('status', __('Only :count of :name are in stock, so your basket now has :count.', [
                'count' => $product->stock,
                'name' => $product->name,
            ]));
        }

        $cart->add($product->id, $validated['quantity'] ?? 1);

        return back()->with('status', __(':name added to your basket.', ['name' => $product->name]));
    }

    public function update(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.Cart::MAX_QUANTITY],
        ]);

        if ($validated['quantity'] > 0 && $validated['quantity'] > $product->stock) {
            if (! $product->isInStock()) {
                return back()->withErrors(['quantity' => __(':name is out of stock.', ['name' => $product->name])]);
            }

            $cart->update($product->id, $product->stock);

            return back()->with('status', __('Only :count of :name are in stock, so your basket now has :count.', [
                'count' => $product->stock,
                'name' => $product->name,
            ]));
        }

        $cart->update($product->id, $validated['quantity']);

        return back()->with('status', __('Basket updated.'));
    }

    public function destroy(Product $product, Cart $cart): RedirectResponse
    {
        $cart->remove($product->id);

        return back()->with('status', __('Item removed from your basket.'));
    }
}
