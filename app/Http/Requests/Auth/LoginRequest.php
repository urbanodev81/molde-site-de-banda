<?php

namespace App\Http\Requests\Auth;

use App\Rules\Captcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public const MAX_POR_IDENTIDADE = 5;

    public const DECAY_IDENTIDADE = 900;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],

            'captcha_token' => Captcha::rules(),
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->throttleKeyPorIdentidade(), self::DECAY_IDENTIDADE);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->throttleKeyPorIdentidade());
    }

    public function ensureIsNotRateLimited(): void
    {
        $porIp = RateLimiter::tooManyAttempts($this->throttleKey(), 5);
        $porIdentidade = RateLimiter::tooManyAttempts($this->throttleKeyPorIdentidade(), self::MAX_POR_IDENTIDADE);

        if (! $porIp && ! $porIdentidade) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            $porIp ? RateLimiter::availableIn($this->throttleKey()) : 0,
            $porIdentidade ? RateLimiter::availableIn($this->throttleKeyPorIdentidade()) : 0,
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    public function throttleKeyPorIdentidade(): string
    {
        return 'login-id:'.Str::transliterate(Str::lower($this->string('email')));
    }
}
