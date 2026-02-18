<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\WelcomeNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        }
        else {
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

        $validationRules = [
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role' => ['required', 'string', 'in:' . implode(',', $availableRoles)],
        ];

        // Solo SuperAdmin puede asignar empresa (para Admins)
        if ($authUser->role === 'SuperAdmin') {
            $validationRules['company'] = ['nullable', 'string', 'max:255'];
        }

        $request->validate($validationRules);

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

        $userData = [
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($randomPassword),
            'created_by' => $authUser->id,
        ];

        // Asignar empresa
        if ($authUser->role === 'SuperAdmin') {
            // SuperAdmin puede asignar empresa manualmente a Admins
            $userData['company'] = $request->company;
        } else {
            // Admin hereda su empresa a los usuarios que crea
            $userData['company'] = $authUser->company;
        }

        $user = User::create($userData);

        // Enviar correo de bienvenida con credenciales
        try {
            $welcomeMail = new WelcomeNewUser($user, $authUser, $randomPassword);
            $welcomeMail->send();
        }
        catch (\Exception $e) {
            Log::error('Error al enviar correo de bienvenida', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

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

        // No permitir eliminar al SuperAdmin protegido
        if (!$user->canBeDeleted()) {
            return redirect()->route('users.index')
                ->withErrors(['error' => 'Este Super Administrador está protegido y no puede ser eliminado.']);
        }

        // Validar permisos de eliminación
        if ($authUser->role === 'Admin') {
            // Admin solo puede eliminar usuarios tipo 'Usuario' que él creó
            if ($user->role !== 'Usuario' || $user->created_by !== $authUser->id) {
                return redirect()->route('users.index')
                    ->withErrors(['error' => 'No tienes permiso para eliminar este usuario.']);
            }
        }
        elseif ($authUser->role === 'SuperAdmin') {
            // SuperAdmin puede eliminar Admin, Usuario y otros SuperAdmin (excepto el protegido)
            // No hay restricción adicional aquí porque ya verificamos canBeDeleted()
        }
        else {
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
    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $authUser = auth()->user();

        // Validar permisos de edición
        if ($authUser->role !== 'SuperAdmin') {
            // Admin solo puede editar usuarios tipo 'Usuario' que él creó
            if ($user->role !== 'Usuario' || $user->created_by !== $authUser->id) {
                return redirect()->route('users.index')
                    ->withErrors(['error' => 'No tienes permiso para editar este usuario.']);
            }
        }

        // No permitir que Admin edite otros Admins (aunque no debería llegar aquí por la lógica anterior, es doble check)
        if ($authUser->role === 'Admin' && $user->role === 'Admin') {
            return redirect()->route('users.index')
                ->withErrors(['error' => 'No tienes permiso para editar otros Administradores.']);
        }

        return view('users.edit-user', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();

        // Validar permisos de edición
        if ($authUser->role !== 'SuperAdmin') {
            if ($user->role !== 'Usuario' || $user->created_by !== $authUser->id) {
                return redirect()->route('users.index')
                    ->withErrors(['error' => 'No tienes permiso para editar este usuario.']);
            }
        }

        $availableRoles = ['Usuario'];
        if ($authUser->role === 'SuperAdmin') {
            $availableRoles = ['SuperAdmin', 'Admin', 'Usuario'];
        }

        $validationRules = [
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            // Si el usuario autenticado es Admin, no permitimos cambiar el rol del usuario (siempre será Usuario)
            // Si es SuperAdmin, sí puede cambiar el rol
            'role' => $authUser->role === 'SuperAdmin' ? ['required', 'string', 'in:' . implode(',', $availableRoles)] : [],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        // Solo SuperAdmin puede editar empresa
        if ($authUser->role === 'SuperAdmin') {
            $validationRules['company'] = ['nullable', 'string', 'max:255'];
        }

        $request->validate($validationRules);

        $userData = [
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
        ];

        // Solo SuperAdmin puede cambiar roles y empresa
        if ($authUser->role === 'SuperAdmin') {
            $userData['role'] = $request->role;
            $userData['company'] = $request->company;
        }

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }
}
