<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait FranchiseHolderValidationRules
{
    /**
     * Get the validation rules used to validate a franchise holder's business/shipping details.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function franchiseHolderRules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'business_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:10'],
        ];
    }
}
