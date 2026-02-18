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
     * Mostrar lista de solicitantes.
     */
    public function index()
    {
        $user = auth()->user();

        // Si es SuperAdmin, mostrar todos los solicitantes
        if ($user->role === 'SuperAdmin') {
            $applicants = MvClientApplicant::with('user')->latest()->get();
            return view('applicants.index', compact('applicants'));
        }

        $ownerEmail = $user->getApplicantOwnerEmail();
        $applicants = MvClientApplicant::with('user')
            ->where('user_email', $ownerEmail)
            ->latest()
            ->get();

        return view('applicants.index', compact('applicants'));
    }

    /**
     * Mostrar el formulario para crear un nuevo solicitante.
     */
    public function create()
    {
        return view('applicants.create');
    }

    /**
     * Almacenar un nuevo solicitante en la base de datos.
     */
    public function store(Request $request)
    {
        // SuperAdmin puede crear solicitantes
        $user = auth()->user();

        $ownerEmail = $user->getApplicantOwnerEmail();

        // Validar límite de solicitantes (basado en el admin dueño)
        $maxApplicants = $user->max_applicants ?? 10;
        $currentCount = \App\Models\MvClientApplicant::where('user_email', $ownerEmail)->count();
        if ($currentCount >= $maxApplicants) {
            return redirect()->back()
                ->withErrors(['limit' => "Has alcanzado el límite máximo de {$maxApplicants} solicitantes. No puedes crear más."])
                ->withInput();
        }

        $validated = $request->validate([
            'applicant_rfc' => ['required', 'string', 'max:13', 'unique:mv_client_applicants,applicant_rfc'],
            'business_name' => ['required', 'string', 'max:255'],
            'applicant_email' => ['required', 'email', 'max:255'],
            'vucem_key_file' => ['nullable', 'file', 'max:10240'],
            'vucem_cert_file' => ['nullable', 'file', 'max:10240'],
            'vucem_password' => ['nullable', 'string', 'max:255'],
            'vucem_webservice_key' => ['nullable', 'string', 'max:500'],
            'privacy_consent' => ['nullable', 'accepted'],
        ]);

        $data = [
            'user_email' => $ownerEmail,
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
     */
    public function edit(MvClientApplicant $applicant)
    {
        return view('applicants.edit', compact('applicant'));
    }

    /**
     * Actualizar un solicitante en la base de datos.
     */
    public function update(Request $request, MvClientApplicant $applicant)
    {
        $validated = $request->validate([
            'applicant_rfc' => ['required', 'string', 'max:13', 'unique:mv_client_applicants,applicant_rfc,' . $applicant->id],
            'business_name' => ['required', 'string', 'max:255'],
            'applicant_email' => ['required', 'email', 'max:255'],
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
     */
    public function destroy(MvClientApplicant $applicant)
    {
        $applicant->delete();

        return redirect()->route('applicants.index')
            ->with('success', 'Solicitante eliminado exitosamente.');
    }
}
