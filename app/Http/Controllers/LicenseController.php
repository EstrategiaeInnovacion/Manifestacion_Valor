<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    /**
     * Panel de administración de licencias (solo SuperAdmin)
     */
    public function index()
    {
        $admins = User::where('role', 'Admin')
            ->with(['licenses' => fn($q) => $q->latest(), 'activeLicense', 'createdUsers'])
            ->get();

        $allLicenses = License::with(['admin', 'creator'])->latest()->get();

        return view('admin.licenses', compact('admins', 'allLicenses'));
    }

    /**
     * Generar e asignar una nueva licencia a un admin
     */
    public function store(Request $request)
    {
        $request->validate([
            'admin_id'      => 'required|exists:users,id',
            'duration_type'  => 'required|in:1min,1month,6months,1year',
            'notes'         => 'nullable|string|max:500',
        ]);

        $admin = User::findOrFail($request->admin_id);

        if ($admin->role !== 'Admin') {
            return back()->withErrors(['admin_id' => 'Solo se pueden asignar licencias a Administradores.']);
        }

        // Revocar licencia activa anterior si existe
        License::where('admin_id', $admin->id)
            ->where('status', 'active')
            ->update(['status' => 'revoked']);

        $startsAt  = now();
        $expiresAt = License::calculateExpiration($request->duration_type, $startsAt);

        $license = License::create([
            'license_key'   => License::generateKey(),
            'admin_id'      => $admin->id,
            'duration_type'  => $request->duration_type,
            'starts_at'     => $startsAt,
            'expires_at'    => $expiresAt,
            'status'        => 'active',
            'created_by'    => auth()->id(),
            'notes'         => $request->notes,
        ]);

        Log::info('[LICENSE] Nueva licencia asignada', [
            'license_key' => $license->license_key,
            'admin'       => $admin->full_name,
            'duration'    => $request->duration_type,
            'expires_at'  => $expiresAt->toDateTimeString(),
        ]);

        return back()->with('success', 
            "Licencia {$license->license_key} asignada a {$admin->full_name}. Expira: {$expiresAt->format('d/m/Y H:i')}"
        );
    }

    /**
     * Renovar (extender) una licencia existente
     */
    public function renew(Request $request, License $license)
    {
        $request->validate([
            'duration_type' => 'required|in:1min,1month,6months,1year',
        ]);

        // Revocar la actual
        $license->update(['status' => 'revoked']);

        // Crear nueva licencia
        $startsAt  = now();
        $expiresAt = License::calculateExpiration($request->duration_type, $startsAt);

        $newLicense = License::create([
            'license_key'   => License::generateKey(),
            'admin_id'      => $license->admin_id,
            'duration_type'  => $request->duration_type,
            'starts_at'     => $startsAt,
            'expires_at'    => $expiresAt,
            'status'        => 'active',
            'created_by'    => auth()->id(),
            'notes'         => 'Renovación de licencia ' . $license->license_key,
        ]);

        Log::info('[LICENSE] Licencia renovada', [
            'old_key' => $license->license_key,
            'new_key' => $newLicense->license_key,
            'admin'   => $license->admin->full_name,
        ]);

        return back()->with('success', 
            "Licencia renovada para {$license->admin->full_name}. Nueva clave: {$newLicense->license_key}. Expira: {$expiresAt->format('d/m/Y H:i')}"
        );
    }

    /**
     * Revocar una licencia activa
     */
    public function revoke(License $license)
    {
        $license->update(['status' => 'revoked']);

        Log::info('[LICENSE] Licencia revocada', [
            'license_key' => $license->license_key,
            'admin'       => $license->admin->full_name,
        ]);

        return back()->with('success', 
            "Licencia {$license->license_key} revocada. {$license->admin->full_name} y sus usuarios ya no podrán acceder."
        );
    }

    /**
     * Actualizar límites de un admin o usuario
     */
    public function updateLimits(Request $request, User $user)
    {
        $request->validate([
            'max_users'      => 'nullable|integer|min:0|max:100',
            'max_applicants' => 'nullable|integer|min:0|max:500',
        ]);

        $data = [];
        if ($request->has('max_users') && in_array($user->role, ['Admin', 'SuperAdmin'])) {
            $data['max_users'] = $request->max_users;
        }
        if ($request->has('max_applicants')) {
            $data['max_applicants'] = $request->max_applicants;
        }

        if (!empty($data)) {
            $user->update($data);
        }

        Log::info('[LICENSE] Límites actualizados', [
            'user'           => $user->full_name,
            'max_users'      => $user->max_users,
            'max_applicants' => $user->max_applicants,
        ]);

        return back()->with('success', 
            "Límites actualizados para {$user->full_name}."
        );
    }
}
