<?php
namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $avisoSellos   = AppSetting::get('aviso_privacidad_sellos');
        $avisoCompleto = AppSetting::get('aviso_privacidad_completo');
        $condicionesUso = AppSetting::get('condiciones_uso');

        return view('admin.settings', compact('avisoSellos', 'avisoCompleto', 'condicionesUso'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'key'   => 'required|in:aviso_privacidad_sellos,aviso_privacidad_completo,condiciones_uso',
            'value' => 'required|string|max:65535',
        ]);

        AppSetting::set($request->input('key'), $request->input('value'));

        return back()->with('success', 'Contenido actualizado correctamente.');
    }
}
