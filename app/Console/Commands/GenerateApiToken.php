<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GenerateApiToken extends Command
{
    protected $signature = 'api:token {email? : Email del usuario} {--days=365 : Días de vigencia}';
    protected $description = 'Generar token API para cliente C#';

    public function handle()
    {
        $email = $this->argument('email');
        
        if (!$email) {
            $email = $this->ask('Ingrese el email del usuario:');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario no encontrado: $email");
            return 1;
        }

        $days = (int) $this->option('days');
        $name = 'vucem-desktop-' . now()->format('YmdHis');
        
        $token = $user->createToken($name, ['*'], now()->addDays($days));
        
        $this->info("========================================");
        $this->info("Usuario: {$user->full_name}");
        $this->info("Email: {$user->email}");
        $this->info("Empresa: {$user->company}");
        $this->info("========================================");
        $this->info("TOKEN API (duración: {$days} días):");
        $this->line("");
        $this->info($token->plainTextToken);
        $this->line("");
        
        return 0;
    }
}