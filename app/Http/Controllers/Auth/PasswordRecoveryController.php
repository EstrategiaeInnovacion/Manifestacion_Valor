<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordVerificationCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    /** Tiempo de vida del código en segundos (5 minutos) */
    private const CODE_TTL = 5 * 60;

    // ──────────────────────────────────────────────────────────
    // PASO 1: Mostrar formulario (usuario o correo)
    // ──────────────────────────────────────────────────────────

    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    // ──────────────────────────────────────────────────────────
    // PASO 1: Buscar usuario, generar y enviar código
    // ──────────────────────────────────────────────────────────

    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'Por favor ingresa tu usuario o correo electrónico.',
        ]);

        $identifier = trim($request->input('identifier'));

        // Buscar por email o por username
        $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $identifier)->first()
            : User::where('username', $identifier)->orWhere('email', $identifier)->first();

        // Siempre redirigir al paso 2 para no revelar si el usuario existe
        if ($user) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $request->session()->put('recovery_email',       $user->email);
            $request->session()->put('recovery_name',        $user->full_name);
            $request->session()->put('recovery_code_hash',   Hash::make($code));
            $request->session()->put('recovery_expires_at',  now()->addSeconds(self::CODE_TTL)->timestamp);
            $request->session()->put('recovery_verified',    false);
            $request->session()->put('recovery_identifier',  $identifier);

            Mail::to($user->email)->send(new PasswordVerificationCode($user->full_name, $code));
        }

        return redirect()->route('password.verify')
            ->with('recovery_sent', true);
    }

    // ──────────────────────────────────────────────────────────
    // PASO 2: Mostrar formulario de verificación de código
    // ──────────────────────────────────────────────────────────

    public function verifyForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('recovery_email')) {
            return redirect()->route('password.request');
        }

        $email     = $request->session()->get('recovery_email');
        $expiresAt = $request->session()->get('recovery_expires_at');
        $expired   = now()->timestamp > $expiresAt;
        $maskedEmail = $this->maskEmail($email);

        return view('auth.verify-code', compact('maskedEmail', 'expired'));
    }

    // ──────────────────────────────────────────────────────────
    // PASO 2: Verificar código ingresado
    // ──────────────────────────────────────────────────────────

    public function verifyCode(Request $request): RedirectResponse
    {
        if (! $request->session()->has('recovery_email')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Por favor ingresa el código de verificación.',
            'code.size'     => 'El código debe ser de 6 dígitos.',
        ]);

        // Verificar expiración
        if (now()->timestamp > $request->session()->get('recovery_expires_at')) {
            return back()->withErrors(['code' => 'El código ha expirado. Por favor solicita uno nuevo.']);
        }

        // Verificar código
        if (! Hash::check($request->input('code'), $request->session()->get('recovery_code_hash'))) {
            return back()->withErrors(['code' => 'El código ingresado es incorrecto.']);
        }

        // Marcar como verificado
        $request->session()->put('recovery_verified', true);

        return redirect()->route('password.reset');
    }

    // ──────────────────────────────────────────────────────────
    // PASO 2 extra: Reenviar / regenerar código
    // ──────────────────────────────────────────────────────────

    public function resendCode(Request $request): RedirectResponse
    {
        if (! $request->session()->has('recovery_email')) {
            return redirect()->route('password.request');
        }

        $email = $request->session()->get('recovery_email');
        $name  = $request->session()->get('recovery_name');
        $code  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put('recovery_code_hash',  Hash::make($code));
        $request->session()->put('recovery_expires_at', now()->addSeconds(self::CODE_TTL)->timestamp);
        $request->session()->put('recovery_verified',   false);

        Mail::to($email)->send(new PasswordVerificationCode($name, $code));

        return redirect()->route('password.verify')
            ->with('recovery_sent', true);
    }

    // ──────────────────────────────────────────────────────────
    // PASO 3: Mostrar formulario de nueva contraseña
    // ──────────────────────────────────────────────────────────

    public function resetForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('recovery_verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    // ──────────────────────────────────────────────────────────
    // PASO 3: Guardar nueva contraseña
    // ──────────────────────────────────────────────────────────

    public function savePassword(Request $request): RedirectResponse
    {
        if (! $request->session()->get('recovery_verified')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'password.required'  => 'La nueva contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $email = $request->session()->get('recovery_email');
        $user  = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'No se encontró el usuario.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        // Limpiar datos de recuperación de la sesión
        $request->session()->forget([
            'recovery_email',
            'recovery_name',
            'recovery_code_hash',
            'recovery_expires_at',
            'recovery_verified',
            'recovery_identifier',
        ]);

        return redirect()->route('login')
            ->with('status', 'Tu contraseña ha sido actualizada. Ya puedes iniciar sesión.');
    }

    // ──────────────────────────────────────────────────────────
    // Helper: Enmascarar email para mostrarlo al usuario
    // ──────────────────────────────────────────────────────────

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 1);
        $masked  = $visible . str_repeat('*', max(strlen($local) - 1, 3));
        return $masked . '@' . $domain;
    }
}
