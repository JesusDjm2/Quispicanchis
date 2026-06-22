<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Los 10 distritos atendidos por la UGEL Quispicanchi, base para el proyecto
 * "PEL Quispicanchi al 2036".
 *
 * Lucre y Oropesa pertenecen politicamente a la provincia de Quispicanchi pero
 * son atendidos por una UGEL distinta: no aparecen en ningun censo ESCALE de
 * UGEL Quispicanchi (verificado con los archivos de matricula 2025 y 2026),
 * y el contrato original habla de "10 distritos". Por eso se excluyen aqui.
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
            ['name' => 'Marcapata', 'ubigeo' => '081209'],
            ['name' => 'Ocongate', 'ubigeo' => '081210'],
            ['name' => 'Quiquijana', 'ubigeo' => '081212'],
        ];

        foreach ($districts as $district) {
            District::query()->updateOrCreate(['name' => $district['name']], $district);
        }
    }
}
