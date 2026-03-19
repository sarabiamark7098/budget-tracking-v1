<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'currency' => $data['currency'] ?? 'PHP',
            'timezone' => $data['timezone'] ?? 'Asia/Manila',
        ]);

        // Seed default categories for new user
        app(CategoryService::class)->seedDefaultCategories($user);

        return $user;
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        } else {
            $user->tokens()->delete();
        }
    }

    public function updateProfile(User $user, array $data): User
    {
        $fillable = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'currency' => $data['currency'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'avatar' => $data['avatar'] ?? null,
        ], fn($v) => !is_null($v));

        $user->update($fillable);

        return $user->fresh();
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => Hash::make($newPassword)]);
    }
}
