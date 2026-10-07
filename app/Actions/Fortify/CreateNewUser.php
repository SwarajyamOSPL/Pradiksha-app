<?php

namespace App\Actions\Fortify;

use App\Concerns\FranchiseHolderValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use FranchiseHolderValidationRules, PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered franchise holder.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            ...$this->franchiseHolderRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => UserRole::FranchiseHolder,
            'status' => UserStatus::Pending,
            'phone' => $input['phone'],
            'business_name' => $input['business_name'],
            'address' => $input['address'],
            'city' => $input['city'],
            'state' => $input['state'],
            'pincode' => $input['pincode'],
        ]);
    }
}
