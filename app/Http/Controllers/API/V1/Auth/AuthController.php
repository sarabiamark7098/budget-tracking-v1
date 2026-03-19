<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $service) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->service->register($request->validated());
        $token = $user->createToken('auth_token')->plainTextToken;
        $user->token = $token;

        return $this->respondCreated(new UserResource($user), 'User registered successfully');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login($request->validated());
        $result['user']->token = $result['token'];

        return $this->respondSuccess(new UserResource($result['user']), 'Login successful');
    }

    public function logout(): JsonResponse
    {
        $this->service->logout(auth()->user());
        return $this->respondSuccess(null, 'Logged out successfully');
    }

    public function me(): JsonResponse
    {
        return $this->respondSuccess(new UserResource(auth()->user()), 'User retrieved successfully');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->service->updateProfile(auth()->user(), $request->validated());
        return $this->respondSuccess(new UserResource($user), 'Profile updated successfully');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->respondError('Current password is incorrect', 422);
        }

        $this->service->changePassword($user, $request->password);
        return $this->respondSuccess(null, 'Password changed successfully');
    }
}
