<?php

namespace App\Http\Controllers;

use App\Models\MvClientApplicant;
use App\Models\User;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    /**
     * Mostrar lista de solicitantes.
     */
    public function index()
    {
        $applicants = MvClientApplicant::with('user')->latest()->get();
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
        $validated = $request->validate([
            'applicant_rfc' => ['required', 'string', 'max:13', 'unique:mv_client_applicants,applicant_rfc'],
            'business_name' => ['required', 'string', 'max:255'],
            'main_economic_activity' => ['required', 'string'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:10'],
            'state' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:255'],
            'exterior_number' => ['required', 'string', 'max:20'],
            'interior_number' => ['nullable', 'string', 'max:20'],
            'area_code' => ['required', 'string', 'max:5'],
            'phone' => ['required', 'string', 'max:20'],
            'ws_file_upload_key' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['user_email'] = auth()->user()->email;
        MvClientApplicant::create($validated);

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
            'main_economic_activity' => ['required', 'string'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:10'],
            'state' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:255'],
            'exterior_number' => ['required', 'string', 'max:20'],
            'interior_number' => ['nullable', 'string', 'max:20'],
            'area_code' => ['required', 'string', 'max:5'],
            'phone' => ['required', 'string', 'max:20'],
            'ws_file_upload_key' => ['nullable', 'string', 'max:255'],
        ]);

        $applicant->update($validated);

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
