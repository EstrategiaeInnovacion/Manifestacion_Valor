<?php

namespace App\Http\Controllers;

use App\Models\UserManual;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserManualController extends Controller
{
    /**
     * Lista todos los manuales disponibles (todos los usuarios autenticados).
     */
    public function index()
    {
        $manuals = UserManual::orderByDesc('created_at')->get();
        return view('manuals.index', compact('manuals'));
    }

    /**
     * Sube una nueva versión del manual (solo SuperAdmin).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'version' => 'required|string|max:50',
            'manual'  => 'required|file|mimes:pdf|max:51200', // 50 MB max
        ]);

        $file     = $request->file('manual');
        $slug     = Str::slug($request->version);
        $filename = 'manual_' . $slug . '_' . time() . '.pdf';

        $file->storeAs('manuals', $filename, 'local');

        UserManual::create([
            'version'       => strtoupper(trim($request->version)),
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Manual "' . strtoupper(trim($request->version)) . '" subido correctamente.');
    }

    /**
     * Sirve el PDF del manual inline en el navegador (todos los usuarios autenticados).
     */
    public function show(UserManual $manual): BinaryFileResponse
    {
        $path = storage_path('app/private/manuals/' . $manual->filename);

        abort_unless(file_exists($path), 404, 'Archivo no encontrado.');

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $manual->original_name . '"',
        ]);
    }

    /**
     * Elimina un manual (solo SuperAdmin).
     */
    public function destroy(UserManual $manual): RedirectResponse
    {
        Storage::disk('local')->delete('manuals/' . $manual->filename);
        $manual->delete();

        return back()->with('success', 'Manual eliminado correctamente.');
    }
}
