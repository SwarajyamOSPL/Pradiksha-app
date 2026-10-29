<?php

namespace App\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\FranchiseApproved;
use Throwable;

class ApproveFranchiseHolder
{
    /**
     * Approve a franchise holder and let them know they can now log in. Approving
     * someone who is already approved does nothing, so they never get a second email.
     */
    public function __invoke(User $holder, bool $notify = true): User
    {
        if ($holder->status === UserStatus::Approved) {
            return $holder;
        }

        $holder->update(['status' => UserStatus::Approved]);

        if ($notify) {
            try {
                $holder->notify(new FranchiseApproved);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $holder;
    }
}
