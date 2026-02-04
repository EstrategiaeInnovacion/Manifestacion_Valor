<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Creamos el usuario SuperAdmin con el nombre completo corregido
        User::create([
            'full_name' => 'Amos Guillermo Aguilera Gonzalez',
            'email'     => 'guillermo.aguilera@estrategiaeinnovacion.com.mx',
            'username'  => 'E&I',
            'password'  => Hash::make('Estrategia1'),
            'role'      => 'SuperAdmin',
        ]);
    }
}