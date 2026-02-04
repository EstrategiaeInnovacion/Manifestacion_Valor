<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     * Reglas de validación actualizadas.
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'], // Este es el campo de Usuario o Correo
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Intento de autenticación con triple validación.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginValue = $this->input('login');
        
        // Determinamos si el valor de "login" es un email o un nombre de usuario
        $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Intentamos autenticar usando (Email o Username) + Password
        $credentials = [
            $loginField => $loginValue,
            'password' => $this->input('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Detener el proceso si hay demasiados intentos fallidos.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Llave para el control de intentos (Rate Limiting).
     * Usamos el RFC y el Login para identificar al usuario de forma única.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('rfc')).'|'.Str::lower($this->string('login')).'|'.$this->ip()
        );
    }
}