<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Los 12 distritos oficiales de la provincia de Quispicanchi (Cusco), con su
 * ubigeo INEI (departamento 08, provincia 12), base para el proyecto
 * "PEL Quispicanchi al 2036".
 */
class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            ['name' => 'Urcos', 'ubigeo' => '081201'],
            ['name' => 'Andahuaylillas', 'ubigeo' => '081202'],
            ['name' => 'Camanti', 'ubigeo' => '081203'],
            ['name' => 'Ccarhuayo', 'ubigeo' => '081204'],
            ['name' => 'Ccatca', 'ubigeo' => '081205'],
            ['name' => 'Cusipata', 'ubigeo' => '081206'],
            ['name' => 'Huaro', 'ubigeo' => '081207'],
            ['name' => 'Lucre', 'ubigeo' => '081208'],
            ['name' => 'Marcapata', 'ubigeo' => '081209'],
            ['name' => 'Ocongate', 'ubigeo' => '081210'],
            ['name' => 'Oropesa', 'ubigeo' => '081211'],
            ['name' => 'Quiquijana', 'ubigeo' => '081212'],
        ];

        foreach ($districts as $district) {
            District::query()->updateOrCreate(['name' => $district['name']], $district);
        }
    }
}
