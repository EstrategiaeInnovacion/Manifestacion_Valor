<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     * 
     * Autentica usuario desde la app C# y devuelve token de acceso.
     * El token se guarda en la configuración local de la app de escritorio.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('[API_AUTH] Intento de login fallido', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'INVALID_CREDENTIALS',
                    'message' => 'Credenciales inválidas. Verifique su email y contraseña.',
                ], 401);
            }

            // Revocar tokens anteriores (opcional - seguridad)
            // $user->tokens()->delete();

            // Crear token de larga duración (1 año)
            $token = $user->createToken(
                'vucem-desktop-' . now()->format('YmdHis'),
                ['*'],
                now()->addYear()
            );

            Log::info('[API_AUTH] Login exitoso desde app desktop', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Autenticación exitosa',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'role' => $user->role,
                    'company' => $user->company,
                ],
                'token' => $token->plainTextToken,
                'expires_at' => now()->addYear()->toIso8601String(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'VALIDATION_ERROR',
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[API_AUTH] Error en login', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'INTERNAL_ERROR',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * POST /api/auth/logout
     * 
     * Invalida el token actual (opcional)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'INTERNAL_ERROR',
                'message' => 'Error al cerrar sesión',
            ], 500);
        }
    }

    /**
     * GET /api/auth/me
     * 
     * Devuelve información del usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $applicants = [];
        
        $applicantModels = \App\Models\MvClientApplicant::where('created_by_user_id', $user->id)
            ->orWhere('user_email', $user->email)
            ->get(['id', 'applicant_rfc', 'business_name', 'applicant_email']);
        
        foreach ($applicantModels as $app) {
            $applicants[] = [
                'id' => $app->id,
                'rfc' => $app->applicant_rfc,
                'razon_social' => $app->business_name,
                'email' => $app->applicant_email,
            ];
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'company' => $user->company,
            ],
            'applicants' => $applicants,
        ], 200);
    }
}