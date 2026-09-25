<?php

namespace Nodex\Nexus\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected AuthThrottler $throttler,
    ) {}

    /**
     * Cart/Wishlist are optional modules: merge guest data only if installed.
     */
    protected function mergeGuestData(Authenticatable $user, string $guestSessionId): void
    {
        foreach ([
            'App\Nexus\Modules\Cart\Services\GuestCartMerger',
            'App\Nexus\Modules\Wishlist\Services\GuestWishlistMerger',
        ] as $merger) {
            if (class_exists($merger)) {
                app($merger)->merge($user, $guestSessionId);
            }
        }
    }

    public function register(array $data): User
    {
        // Captured before Auth::login() below: SessionGuard::login() already
        // regenerates the session id internally (see GuestCartMerger's
        // docblock), so the guest's id has to be grabbed before that call.
        $guestSessionId = session()->getId();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        //         $user->sendEmailVerificationNotification();

        Auth::login($user);

        $this->mergeGuestData($user, $guestSessionId);

        return $user;
    }

    public function login(array $data, Request $request): void
    {
        $guestSessionId = session()->getId();

        $throttleKey = $this->throttler->keyFor($data['email'], $request->ip());
        $this->throttler->ensureNotLimited($throttleKey, $request);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
        $remember = (bool) ($data['remember'] ?? false);

        if (! Auth::attempt($credentials, $remember)) {
            $this->throttler->hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth::translate.incorrect_credential'),
            ]);
        }

        $this->throttler->clear($throttleKey);

        $this->mergeGuestData(Auth::user(), $guestSessionId);
    }

    public function logout(): void
    {
        // Explicit 'web' guard, not the bare Auth facade: apiLogout()'s route
        // carries auth:sanctum, and Illuminate\Auth\Middleware\Authenticate
        // calls Auth::shouldUse('sanctum') the moment that guard succeeds —
        // making the facade's default guard 'sanctum' for the rest of the
        // request. RequestGuard (Sanctum's guard class) has no logout()
        // method at all, so the bare Auth::logout() call this replaced threw
        // a BadMethodCallException on that route specifically.
        Auth::guard('web')->logout();
    }
}
