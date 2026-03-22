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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $service) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user  = $this->service->register($request->validated());
        $token = $user->createToken('auth_token')->plainTextToken;
        $user->token = $token;

        // S-02: Create HttpOnly session cookie for SPA cookie auth.
        // When the request comes through the Sanctum stateful middleware pipeline
        // (i.e. from a configured SPA domain), the session is started automatically.
        // auth('web')->login() sets the session user, and Laravel writes the
        // laravel_session HttpOnly cookie in the response.
        $this->startSpaSession($user);

        return $this->respondCreated(new UserResource($user), 'User registered successfully');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login($request->validated());
        $result['user']->token = $result['token'];

        // S-02: Also create SPA session for HttpOnly cookie auth.
        $this->startSpaSession($result['user']);

        return $this->respondSuccess(new UserResource($result['user']), 'Login successful');
    }

    public function logout(): JsonResponse
    {
        // Revoke the Sanctum API token (for token-based clients).
        $this->service->logout(auth()->user());

        // S-02: Also invalidate the SPA session (for cookie-based clients).
        if (request()->hasSession()) {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return $this->respondSuccess(null, 'Logged out successfully');
    }

    /**
     * Session probe — intentionally public (no auth middleware).
     *
     * Returns 200 with the authenticated user when a valid session/token exists,
     * or 200 with data: null when there is no active session.
     * Keeping this 200-always prevents a red browser console error on every
     * fresh page load when the SPA probes for an existing session at boot.
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return $this->respondSuccess(null, 'Not authenticated');
        }

        return $this->respondSuccess(new UserResource($user), 'User retrieved successfully');
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

    /**
     * S-02: Log the user into the web guard to create an HttpOnly session cookie.
     * Only runs when the session middleware is active (stateful SPA requests).
     * Safe to call for non-stateful requests — the session simply won't be present.
     */
    private function startSpaSession(mixed $user): void
    {
        if (request()->hasSession()) {
            Auth::guard('web')->login($user);
            request()->session()->regenerate();
        }
    }
}
