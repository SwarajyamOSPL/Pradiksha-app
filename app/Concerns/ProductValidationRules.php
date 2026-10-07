<?php

namespace App\Concerns;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProductValidationRules
{
    /**
     * Get the validation rules used to validate a product.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function productRules(?int $productId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                $productId === null
                    ? Rule::unique(Product::class)
                    : Rule::unique(Product::class)->ignore($productId),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
