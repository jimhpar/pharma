<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        try {
            if (Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
                RateLimiter::clear($this->throttleKey());
                return;
            }
        } catch (RuntimeException) {
            if ($this->attemptLegacyAuthentication()) {
                RateLimiter::clear($this->throttleKey());
                return;
            }
        }

        if ($this->attemptLegacyAuthentication()) {
            RateLimiter::clear($this->throttleKey());
            return;
        }

        if (! Auth::check()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Allow one-time migration from legacy plain-text or MD5 passwords.
     */
    protected function attemptLegacyAuthentication(): bool
    {
        $user = User::query()->where('email', $this->string('email'))->first();

        if (! $user || ! is_string($user->password)) {
            return false;
        }

        $storedPassword = $user->password;
        $plainPassword = (string) $this->string('password');

        $matchesPlainText = hash_equals($storedPassword, $plainPassword);
        $matchesMd5 = strlen($storedPassword) === 32
            && ctype_xdigit($storedPassword)
            && hash_equals(strtolower($storedPassword), md5($plainPassword));

        if (! $matchesPlainText && ! $matchesMd5) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($plainPassword),
        ])->save();

        Auth::login($user, $this->boolean('remember'));

        return true;
    }
}
