<?php

namespace App\Http\Controllers;

use App\Mail\ApplicantAdded;
use App\Models\MvClientApplicant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplicantController extends Controller
{
    /**
     * Mostrar lista de solicitantes según el rol del usuario.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'SuperAdmin') {
            // Obtener todos los usuarios (Admin y Usuario) de la misma empresa
            $usersInCompany = User::where('company', $user->company)
                ->whereIn('role', ['Admin', 'Usuario'])
                ->pluck('id');
            
            // Obtener emails de usuarios de la empresa para solicitantes antiguos
            $emailsInCompany = User::where('company', $user->company)
                ->pluck('email');
            
            $applicants = MvClientApplicant::with(['user', 'assignedUser', 'createdByUser'])
                ->where(function($query) use ($user, $usersInCompany, $emailsInCompany) {
                    // Sus propios solicitantes
                    $query->where('created_by_user_id', $user->id)
                        // O solicitantes creados por usuarios de su empresa
                        ->orWhereIn('created_by_user_id', $usersInCompany)
                        // O solicitantes antiguos (sin created_by_user_id) de emails de su empresa
                        ->orWhere(function($q) use ($emailsInCompany) {
                            $q->whereNull('created_by_user_id')
                              ->whereIn('user_email', $emailsInCompany);
                        });
                })
                ->latest()
                ->get();
            
            return view('applicants.index', compact('applicants'));
        }

        if ($user->role === 'Admin') {
            // Obtener todos los usuarios de la misma empresa
            $usersInCompany = User::where('company', $user->company)
                ->whereIn('role', ['Admin', 'Usuario', 'SuperAdmin'])
                ->pluck('id');
            
            // Obtener emails de usuarios de la empresa para solicitantes antiguos
            $emailsInCompany = User::where('company', $user->company)
                ->pluck('email');
            
            $applicants = MvClientApplicant::with(['user', 'assignedUser', 'createdByUser'])
                ->where(function($query) use ($user, $usersInCompany, $emailsInCompany) {
                    // Sus propios solicitantes
                    $query->where('created_by_user_id', $user->id)
                        // O solicitantes creados por usuarios de su empresa
                        ->orWhereIn('created_by_user_id', $usersInCompany)
                        // O solicitantes antiguos (sin created_by_user_id) de emails de su empresa
                        ->orWhere(function($q) use ($emailsInCompany) {
                            $q->whereNull('created_by_user_id')
                              ->whereIn('user_email', $emailsInCompany);
                        });
                })
                ->latest()
                ->get();
            
            return view('applicants.index', compact('applicants'));
        }

        // Usuario regular: solo ve el solicitante que tiene asignado
        $applicants = MvClientApplicant::with(['user', 'assignedUser', 'createdByUser'])
            ->where('assigned_user_id', $user->id)
            ->latest()
            ->get();

        return view('applicants.index', compact('applicants'));
    }

    /**
     * Mostrar el formulario para crear un nuevo solicitante.
     * SuperAdmin y Admin pueden crear solicitantes.
     */
    public function create()
    {
        $user = auth()->user();
        
        // Solo SuperAdmin y Admin pueden crear solicitantes
        if (!in_array($user->role, ['SuperAdmin', 'Admin'])) {
            return redirect()->route('applicants.index')
                ->withErrors(['error' => 'Solo los administradores pueden crear solicitantes.']);
        }

        // Obtener usuarios para asignación
        if ($user->role === 'SuperAdmin') {
            // SuperAdmin puede asignar a usuarios de toda su empresa
            $usersForAssignment = User::where('company', $user->company)
                ->where('role', 'Usuario')
                ->select('id', 'full_name', 'email')
                ->get();
        } else {
            // Admin solo puede asignar a usuarios que él creó
            $usersForAssignment = User::where('created_by', $user->id)
                ->where('role', 'Usuario')
                ->select('id', 'full_name', 'email')
                ->get();
        }

        return view('applicants.create', compact('usersForAssignment'));
    }

    /**
     * Almacenar un nuevo solicitante en la base de datos.
     * SuperAdmin y Admin pueden crear solicitantes.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Solo SuperAdmin y Admin pueden crear solicitantes
        if (!in_array($user->role, ['SuperAdmin', 'Admin'])) {
            return redirect()->route('applicants.index')
                ->withErrors(['error' => 'Solo los administradores pueden crear solicitantes.']);
        }

        $ownerEmail = $user->email;

        // Validar límite de solicitantes (solo para Admin)
        if ($user->role === 'Admin') {
            $maxApplicants = $user->max_applicants ?? 10;
            $currentCount = MvClientApplicant::where('created_by_user_id', $user->id)->count();
            if ($currentCount >= $maxApplicants) {
                return redirect()->back()
                    ->withErrors(['limit' => "Has alcanzado el límite máximo de {$maxApplicants} solicitantes. No puedes crear más."])
                    ->withInput();
            }
        }

        // Obtener IDs de usuarios válidos para asignación
        if ($user->role === 'SuperAdmin') {
            $validUserIds = User::where('company', $user->company)
                ->where('role', 'Usuario')
                ->pluck('id')
                ->toArray();
        } else {
            $validUserIds = User::where('created_by', $user->id)
                ->where('role', 'Usuario')
                ->pluck('id')
                ->toArray();
        }

        $validationRules = [
            'applicant_rfc' => ['required', 'string', 'max:13', 'unique:mv_client_applicants,applicant_rfc'],
            'business_name' => ['required', 'string', 'max:255'],
            'applicant_email' => ['required', 'email', 'max:255'],
            'vucem_key_file' => ['nullable', 'file', 'max:10240'],
            'vucem_cert_file' => ['nullable', 'file', 'max:10240'],
            'vucem_password' => ['nullable', 'string', 'max:255'],
            'vucem_webservice_key' => ['nullable', 'string', 'max:500'],
            'privacy_consent' => ['nullable', 'accepted'],
        ];

        // Solo validar assigned_user_id si hay usuarios válidos
        if (!empty($validUserIds)) {
            $validationRules['assigned_user_id'] = ['nullable', 'integer', 'in:' . implode(',', $validUserIds)];
        }

        $validated = $request->validate($validationRules);

        $data = [
            'user_email' => $ownerEmail,
            'created_by_user_id' => $user->id,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'applicant_rfc' => $validated['applicant_rfc'],
            'business_name' => $validated['business_name'],
            'applicant_email' => $validated['applicant_email'],
        ];

        // Procesar archivos VUCEM si se proporcionaron
        if ($request->hasFile('vucem_key_file')) {
            $data['vucem_key_file'] = base64_encode(
                file_get_contents($request->file('vucem_key_file')->getRealPath())
            );
        }

        if ($request->hasFile('vucem_cert_file')) {
            $data['vucem_cert_file'] = base64_encode(
                file_get_contents($request->file('vucem_cert_file')->getRealPath())
            );
        }

        if (!empty($validated['vucem_password'])) {
            $data['vucem_password'] = $validated['vucem_password'];
        }

        if (!empty($validated['vucem_webservice_key'])) {
            $data['vucem_webservice_key'] = $validated['vucem_webservice_key'];
        }

        // Consentimiento de privacidad
        if ($request->has('privacy_consent')) {
            $data['privacy_consent'] = true;
            $data['privacy_consent_at'] = now();
        }

        $applicant = MvClientApplicant::create($data);

        // Enviar correo de notificación al usuario
        try {
            (new ApplicantAdded($user, $applicant))->send();
        }
        catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de nuevo solicitante: ' . $e->getMessage());
        }

        return redirect()->route('applicants.index')
            ->with('success', 'Solicitante registrado exitosamente.');
    }

    /**
     * Mostrar los detalles de un solicitante específico.
     */
    public function show(MvClientApplicant $applicant)
    {
        $applicant->load('user');
        return view('applicants.show', compact('applicant'));
    }

    /**
     * Mostrar el formulario para editar un solicitante.
     * SuperAdmin y Admin pueden editar solicitantes de su organización.
     */
    public function edit(MvClientApplicant $applicant)
    {
        $user = auth()->user();

        // Verificar permisos según rol
        if ($user->role === 'SuperAdmin') {
            // SuperAdmin puede editar solicitantes de su empresa
            $companyUserIds = User::where('company', $user->company)
                ->pluck('id')
                ->toArray();
            $companyUserEmails = User::where('company', $user->company)
                ->pluck('email')
                ->toArray();

            $canEdit = $applicant->created_by_user_id === $user->id
                || in_array($applicant->created_by_user_id, $companyUserIds)
                || (is_null($applicant->created_by_user_id) && in_array($applicant->user_email, $companyUserEmails));

            if (!$canEdit) {
                return redirect()->route('applicants.index')
                    ->withErrors(['error' => 'No tienes permiso para editar este solicitante.']);
            }

            // Usuarios disponibles para asignación (todos los Usuario de la empresa)
            $usersForAssignment = User::where('company', $user->company)
                ->where('role', 'Usuario')
                ->select('id', 'full_name', 'email')
                ->get();
        }
        elseif ($user->role === 'Admin') {
            // Admin solo edita solicitantes que creó
            if ($applicant->created_by_user_id !== $user->id) {
                // Verificar si es solicitante legacy de su email
                if (!is_null($applicant->created_by_user_id) || $applicant->user_email !== $user->email) {
                    return redirect()->route('applicants.index')
                        ->withErrors(['error' => 'No tienes permiso para editar este solicitante.']);
                }
            }

            // Usuarios creados por este Admin
            $usersForAssignment = User::where('created_by', $user->id)
                ->where('role', 'Usuario')
                ->select('id', 'full_name', 'email')
                ->get();
        }
        else {
            // Usuario no puede editar
            return redirect()->route('applicants.index')
                ->withErrors(['error' => 'No tienes permiso para editar solicitantes.']);
        }

        return view('applicants.edit', compact('applicant', 'usersForAssignment'));
    }

    /**
     * Actualizar un solicitante en la base de datos.
     * SuperAdmin y Admin pueden actualizar solicitantes de su organización.
     */
    public function update(Request $request, MvClientApplicant $applicant)
    {
        $user = auth()->user();

        // Verificar permisos según rol
        if ($user->role === 'SuperAdmin') {
            // SuperAdmin puede actualizar solicitantes de su empresa
            $companyUserIds = User::where('company', $user->company)
                ->pluck('id')
                ->toArray();
            $companyUserEmails = User::where('company', $user->company)
                ->pluck('email')
                ->toArray();

            $canUpdate = $applicant->created_by_user_id === $user->id
                || in_array($applicant->created_by_user_id, $companyUserIds)
                || (is_null($applicant->created_by_user_id) && in_array($applicant->user_email, $companyUserEmails));

            if (!$canUpdate) {
                return redirect()->route('applicants.index')
                    ->withErrors(['error' => 'No tienes permiso para actualizar este solicitante.']);
            }

            // Usuarios disponibles para asignación (todos los Usuario de la empresa)
            $validUserIds = User::where('company', $user->company)
                ->where('role', 'Usuario')
                ->pluck('id')
                ->toArray();
        }
        elseif ($user->role === 'Admin') {
            // Admin solo actualiza solicitantes que creó
            if ($applicant->created_by_user_id !== $user->id) {
                // Verificar si es solicitante legacy de su email
                if (!is_null($applicant->created_by_user_id) || $applicant->user_email !== $user->email) {
                    return redirect()->route('applicants.index')
                        ->withErrors(['error' => 'No tienes permiso para actualizar este solicitante.']);
                }
            }

            // Usuarios creados por este Admin
            $validUserIds = User::where('created_by', $user->id)
                ->where('role', 'Usuario')
                ->pluck('id')
                ->toArray();
        }
        else {
            // Usuario no puede actualizar
            return redirect()->route('applicants.index')
                ->withErrors(['error' => 'No tienes permiso para actualizar solicitantes.']);
        }


        $validated = $request->validate([
            'applicant_rfc' => ['required', 'string', 'max:13', 'unique:mv_client_applicants,applicant_rfc,' . $applicant->id],
            'business_name' => ['required', 'string', 'max:255'],
            'applicant_email' => ['required', 'email', 'max:255'],
            'assigned_user_id' => ['nullable', 'integer', 'in:' . implode(',', array_merge([0], $validUserIds))],
            'vucem_key_file' => ['nullable', 'file', 'max:10240'],
            'vucem_cert_file' => ['nullable', 'file', 'max:10240'],
            'vucem_password' => ['nullable', 'string', 'max:255'],
            'vucem_webservice_key' => ['nullable', 'string', 'max:500'],
            'privacy_consent' => ['nullable', 'accepted'],
        ]);

        $data = [
            'applicant_rfc' => $validated['applicant_rfc'],
            'business_name' => $validated['business_name'],
            'applicant_email' => $validated['applicant_email'],
            'assigned_user_id' => $validated['assigned_user_id'] ?: null,
        ];

        // Procesar archivos VUCEM si se proporcionaron (reemplazan los existentes)
        if ($request->hasFile('vucem_key_file')) {
            $data['vucem_key_file'] = base64_encode(
                file_get_contents($request->file('vucem_key_file')->getRealPath())
            );
        }

        if ($request->hasFile('vucem_cert_file')) {
            $data['vucem_cert_file'] = base64_encode(
                file_get_contents($request->file('vucem_cert_file')->getRealPath())
            );
        }

        // Si se envía contraseña vacía y se pide eliminar, limpiar
        if ($request->has('clear_vucem_password') && $request->input('clear_vucem_password')) {
            $data['vucem_password'] = null;
        }
        elseif (!empty($validated['vucem_password'])) {
            $data['vucem_password'] = $validated['vucem_password'];
        }

        if ($request->has('clear_vucem_webservice') && $request->input('clear_vucem_webservice')) {
            $data['vucem_webservice_key'] = null;
        }
        elseif (!empty($validated['vucem_webservice_key'])) {
            $data['vucem_webservice_key'] = $validated['vucem_webservice_key'];
        }

        // Si solicita eliminar archivos
        if ($request->has('clear_vucem_key') && $request->input('clear_vucem_key')) {
            $data['vucem_key_file'] = null;
        }
        if ($request->has('clear_vucem_cert') && $request->input('clear_vucem_cert')) {
            $data['vucem_cert_file'] = null;
        }

        // Consentimiento de privacidad
        if ($request->has('privacy_consent') && !$applicant->privacy_consent) {
            $data['privacy_consent'] = true;
            $data['privacy_consent_at'] = now();
        }

        $applicant->update($data);

        return redirect()->route('applicants.index')
            ->with('success', 'Solicitante actualizado exitosamente.');
    }

    /**
     * Eliminar un solicitante de la base de datos.
     * SuperAdmin y Admin pueden eliminar solicitantes de su organización.
     */
    public function destroy(MvClientApplicant $applicant)
    {
        $user = auth()->user();

        // Verificar permisos según rol
        if ($user->role === 'SuperAdmin') {
            // SuperAdmin puede eliminar solicitantes de su empresa
            $companyUserIds = User::where('company', $user->company)
                ->pluck('id')
                ->toArray();
            $companyUserEmails = User::where('company', $user->company)
                ->pluck('email')
                ->toArray();

            $canDelete = $applicant->created_by_user_id === $user->id
                || in_array($applicant->created_by_user_id, $companyUserIds)
                || (is_null($applicant->created_by_user_id) && in_array($applicant->user_email, $companyUserEmails));

            if (!$canDelete) {
                return redirect()->route('applicants.index')
                    ->withErrors(['error' => 'No tienes permiso para eliminar este solicitante.']);
            }
        }
        elseif ($user->role === 'Admin') {
            // Admin solo elimina solicitantes que creó
            if ($applicant->created_by_user_id !== $user->id) {
                // Verificar si es solicitante legacy de su email
                if (!is_null($applicant->created_by_user_id) || $applicant->user_email !== $user->email) {
                    return redirect()->route('applicants.index')
                        ->withErrors(['error' => 'No tienes permiso para eliminar este solicitante.']);
                }
            }
        }
        else {
            // Usuario no puede eliminar
            return redirect()->route('applicants.index')
                ->withErrors(['error' => 'No tienes permiso para eliminar solicitantes.']);
        }

        $applicant->delete();

        return redirect()->route('applicants.index')
            ->with('success', 'Solicitante eliminado exitosamente.');
    }
}
