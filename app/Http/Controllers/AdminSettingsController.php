<?php
namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $avisoSellos    = AppSetting::get('aviso_privacidad_sellos');
        $avisoCompleto  = AppSetting::get('aviso_privacidad_completo');
        $condicionesUso = AppSetting::get('condiciones_uso');
        $manuals        = \App\Models\UserManual::orderByDesc('created_at')->get();
        $announcements  = \App\Models\Announcement::with('creator')->latest()->get();
        $bannerEnabled  = AppSetting::get('banner_enabled', '0') === '1';
        $bannerMessage  = AppSetting::get('banner_message', '');

        return view('admin.settings', compact(
            'avisoSellos', 'avisoCompleto', 'condicionesUso',
            'manuals', 'announcements', 'bannerEnabled', 'bannerMessage'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'key'   => 'required|in:aviso_privacidad_sellos,aviso_privacidad_completo,condiciones_uso,banner_message,banner_enabled',
            'value' => 'nullable|string|max:65535',
        ]);

        AppSetting::set($request->input('key'), $request->input('value', ''));

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
