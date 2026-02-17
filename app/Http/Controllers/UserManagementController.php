<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * Mostrar lista de usuarios.
     */
    public function index()
    {
        $authUser = auth()->user();
        
        if ($authUser->role === 'SuperAdmin') {
            // Para SuperAdmin: mostrar todos los usuarios separados por rol
            $superAdmins = User::where('role', 'SuperAdmin')->with('createdUsers')->get();
            $admins = User::where('role', 'Admin')->with('createdUsers')->get();
            $usuarios = User::where('role', 'Usuario')->get();
            
            return view('users.index', compact('superAdmins', 'admins', 'usuarios'));
        } else {
            // Para Admin: mostrar solo sus usuarios creados
            $usuarios = User::where('created_by', $authUser->id)->get();
            return view('users.index', compact('usuarios'));
        }
    }
    
    /**
     * Mostrar el formulario para crear un nuevo usuario.
     */
    public function create()
    {
        return view('users.add-user');
    }

    /**
     * Almacenar un nuevo usuario en la base de datos.
     */
    public function store(Request $request)
    {
        $authUser = auth()->user();
        
        // Validar que Admin solo pueda crear usuarios tipo Usuario
        $availableRoles = ['Usuario'];
        if ($authUser->role === 'SuperAdmin') {
            $availableRoles = ['SuperAdmin', 'Admin', 'Usuario'];
        }
        
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role' => ['required', 'string', 'in:' . implode(',', $availableRoles)],
        ]);
        
        // Validar límite de usuarios para administradores
        if ($authUser->role === 'Admin') {
            $userCount = User::where('created_by', $authUser->id)->count();
            $maxUsers = $authUser->max_users ?? 5;
            if ($userCount >= $maxUsers) {
                return redirect()->back()
                    ->withErrors(['limit' => "Has alcanzado el límite máximo de {$maxUsers} usuarios. No puedes crear más usuarios."])
                    ->withInput();
            }
        }

        // Generar contraseña aleatoria
        $randomPassword = Str::random(12);

        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($randomPassword),
            'created_by' => $authUser->id,
        ]);

        return redirect()->route('users.create')
            ->with('success', 'Usuario creado exitosamente.')
            ->with('password', $randomPassword)
            ->with('email', $user->email);
    }

    /**
     * Eliminar un usuario de la base de datos.
     */
    public function destroy(User $user)
    {
        $authUser = auth()->user();

        // Validar permisos de eliminación
        if ($authUser->role === 'Admin') {
            // Admin solo puede eliminar usuarios tipo 'Usuario' que él creó
            if ($user->role !== 'Usuario' || $user->created_by !== $authUser->id) {
                return redirect()->route('users.index')
                    ->withErrors(['error' => 'No tienes permiso para eliminar este usuario.']);
            }
        } elseif ($authUser->role === 'SuperAdmin') {
            // SuperAdmin puede eliminar Admin y Usuario, pero no otros SuperAdmin
            if ($user->role === 'SuperAdmin') {
                return redirect()->route('users.index')
                    ->withErrors(['error' => 'No puedes eliminar otros Super Administradores.']);
            }
        } else {
            // Usuario regular no puede eliminar a nadie
            return redirect()->route('users.index')
                ->withErrors(['error' => 'No tienes permisos para eliminar usuarios.']);
        }

        // No permitir auto-eliminación
        if ($user->id === $authUser->id) {
            return redirect()->route('users.index')
                ->withErrors(['error' => 'No puedes eliminarte a ti mismo.']);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
