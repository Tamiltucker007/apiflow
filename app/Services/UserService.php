<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function create(Merchant $merchant, array $data): User
    {
        return User::create([
            'merchant_id' => $merchant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::MerchantAdmin,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);
    }

    public function update(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        return $user;
    }

    /**
     * Toggles is_active. Refuses to deactivate yourself (avoids locking
     * yourself out) or the merchant's last active user (avoids leaving the
     * merchant with no one able to log in and manage it).
     */
    public function toggleActive(User $user, User $actingUser): User
    {
        if ($user->is_active) {
            if ($user->id === $actingUser->id) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot deactivate your own account.',
                ]);
            }

            $this->guardLastActiveUser($user);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return $user;
    }

    private function guardLastActiveUser(User $user): void
    {
        $otherActiveUsers = User::where('merchant_id', $user->merchant_id)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->exists();

        if (! $otherActiveUsers) {
            throw ValidationException::withMessages([
                'user' => 'This is the only active user for this merchant — activate another user first.',
            ]);
        }
    }
}
