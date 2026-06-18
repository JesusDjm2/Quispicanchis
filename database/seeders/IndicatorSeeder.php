<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Database\Seeder;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Indicadores educativos base solicitados para el diagnostico del PEL
 * Quispicanchi al 2036 (fuentes: UGEL, ESCALE, INEI, MIDIS).
 */
class IndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = [
            ['name' => 'Matricula', 'unit' => 'estudiantes', 'description' => 'Total de estudiantes matriculados por nivel educativo.'],
            ['name' => 'Logros de aprendizaje - Lectura', 'unit' => '% nivel satisfactorio', 'description' => 'Porcentaje de estudiantes con logro satisfactorio en comprension lectora (Evaluacion Censal de Estudiantes).'],
            ['name' => 'Logros de aprendizaje - Matematica', 'unit' => '% nivel satisfactorio', 'description' => 'Porcentaje de estudiantes con logro satisfactorio en matematica (Evaluacion Censal de Estudiantes).'],
            ['name' => 'Tasa de desercion escolar', 'unit' => '%', 'description' => 'Porcentaje de estudiantes que abandonan el sistema educativo durante el año escolar.'],
            ['name' => 'Tasa de conclusion primaria', 'unit' => '%', 'description' => 'Porcentaje de poblacion que culmina la educacion primaria en la edad normativa.'],
            ['name' => 'Tasa de conclusion secundaria', 'unit' => '%', 'description' => 'Porcentaje de poblacion que culmina la educacion secundaria en la edad normativa.'],
            ['name' => 'Docentes', 'unit' => 'docentes', 'description' => 'Numero de docentes en ejercicio por distrito.'],
            ['name' => 'Instituciones educativas', 'unit' => 'IIEE', 'description' => 'Numero de instituciones educativas activas.'],
            ['name' => 'Desnutricion cronica infantil', 'unit' => '%', 'description' => 'Porcentaje de niños menores de 5 años con desnutricion cronica (fuente MIDIS).'],
            ['name' => 'Pobreza monetaria', 'unit' => '%', 'description' => 'Porcentaje de poblacion en situacion de pobreza monetaria (fuente INEI).'],
        ];

        foreach ($indicators as $indicator) {
            Indicator::query()->updateOrCreate(['name' => $indicator['name']], $indicator);
        }
    }
}
