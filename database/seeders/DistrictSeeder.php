<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Los 12 distritos politicos de la provincia de Quispicanchi.
 *
 * 10 de ellos (managed_by_ugel = true) son atendidos directamente por la
 * UGEL Quispicanchi: aparecen en sus censos ESCALE y reciben matricula,
 * docentes, IIEE, etc.
 *
 * Lucre y Oropesa (managed_by_ugel = false) pertenecen politicamente a la
 * provincia pero son atendidos por una UGEL distinta: no aparecen en ningun
 * censo ESCALE de UGEL Quispicanchi (verificado con los archivos de
 * matricula 2025 y 2026). Aun asi, el contrato exige incluirlos con sus
 * datos INEI/MIDIS/ESCALE para el consolidado provincial.
 */
class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            ['name' => 'Urcos', 'ubigeo' => '081201', 'managed_by_ugel' => true],
            ['name' => 'Andahuaylillas', 'ubigeo' => '081202', 'managed_by_ugel' => true],
            ['name' => 'Camanti', 'ubigeo' => '081203', 'managed_by_ugel' => true],
            ['name' => 'Ccarhuayo', 'ubigeo' => '081204', 'managed_by_ugel' => true],
            ['name' => 'Ccatca', 'ubigeo' => '081205', 'managed_by_ugel' => true],
            ['name' => 'Cusipata', 'ubigeo' => '081206', 'managed_by_ugel' => true],
            ['name' => 'Huaro', 'ubigeo' => '081207', 'managed_by_ugel' => true],
            ['name' => 'Lucre', 'ubigeo' => '081208', 'managed_by_ugel' => false],
            ['name' => 'Marcapata', 'ubigeo' => '081209', 'managed_by_ugel' => true],
            ['name' => 'Ocongate', 'ubigeo' => '081210', 'managed_by_ugel' => true],
            ['name' => 'Oropesa', 'ubigeo' => '081211', 'managed_by_ugel' => false],
            ['name' => 'Quiquijana', 'ubigeo' => '081212', 'managed_by_ugel' => true],
        ];

        foreach ($districts as $district) {
            District::query()->updateOrCreate(['name' => $district['name']], $district);
        }
    }
}
