<?php

namespace Database\Seeders;

use App\Models\CoveUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CoveUnitSeeder extends Seeder
{
    /**
     * Seed the cove_units table with UN/CEFACT codes from scratch/units.json.
     */
    public function run(): void
    {
        $jsonPath = base_path('scratch/units.json');

        if (!File::exists($jsonPath)) {
            $this->command->warn('⚠️  scratch/units.json no encontrado. Saltando seed de cove_units.');
            return;
        }

        $units = json_decode(File::get($jsonPath), true);

        if (empty($units)) {
            $this->command->warn('⚠️  scratch/units.json está vacío o no es válido.');
            return;
        }

        $count = 0;
        foreach ($units as $unit) {
            $code = $unit['code'] ?? null;
            $name = $unit['name'] ?? null;

            if (!$code || !$name) {
                continue;
            }

            CoveUnit::firstOrCreate(
                ['cove_code' => $code],
                ['name' => $name]
            );
            $count++;
        }

        $this->command->info("✅ {$count} unidades de medida insertadas en cove_units.");
    }
}
