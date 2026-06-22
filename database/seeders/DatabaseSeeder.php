<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Configuracion inicial del sistema "PEL Quispicanchi al 2036".
 * Puebla las tablas maestras con los datos oficiales de la provincia:
 *   - 12 distritos de Quispicanchi con su ubigeo INEI.
 *   - Indicador base 'Matrícula Escolar' (id: 1).
 *   - Usuario administrador por defecto.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // === USUARIO ADMINISTRADOR POR DEFECTO ===
        User::factory()->create([
            'name'  => 'Admin PEL Quispicanchi',
            'email' => 'admin@pel-quispicanchi.edu.pe',
        ]);

        // === TABLAS MAESTRAS DEL SISTEMA ===
        $this->call([
            DistrictSeeder::class,
            IndicatorSeeder::class,
        ]);
    }
}
