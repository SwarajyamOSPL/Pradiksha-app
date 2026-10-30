<?php

namespace App\Concerns;

use App\Enums\DiscountType;
use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PackageValidationRules
{
    /**
     * Get the validation rules used to validate a package.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function packageRules(?int $packageId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                $packageId === null
                    ? Rule::unique(Package::class)
                    : Rule::unique(Package::class)->ignore($packageId),
            ],
            'description' => ['nullable', 'string'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => [
                'nullable', 'numeric', 'min:0',
                Rule::when($this->discount_type === DiscountType::Percent->value, ['max:100']),
            ],
            'fixed_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'goods_worth' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'inclusionsText' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'products' => ['array'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
