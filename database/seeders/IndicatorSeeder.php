<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Database\Seeder;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Indicadores educativos base del sistema "PEL Quispicanchi al 2036".
 * El indicador id:1 es 'Matrícula' (unico, consolida todos los niveles y
 * modalidades), usado como base para la importacion consolidada de la
 * UGEL Quispicanchi. No crear indicadores de matricula adicionales por
 * nivel (EBE, Secundaria, etc.): los valores de cada nivel se suman dentro
 * de este unico indicador, no se reportan por separado.
 *
 * Fuentes oficiales: UGEL, ESCALE, INEI, MIDIS.
 */
class IndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = [
            // === INDICADOR BASE (id:1) ===
            [
                'name'        => 'Matrícula',
                'unit'        => 'estudiantes',
                'description' => 'Total de estudiantes matriculados (todos los niveles y modalidades) por distrito en la provincia de Quispicanchi. Fuente: UGEL Quispicanchi / ESCALE.',
            ],

            [
                'name'        => 'Logros de aprendizaje - Lectura',
                'unit'        => '% nivel satisfactorio',
                'description' => 'Porcentaje de estudiantes con logro satisfactorio en comprension lectora (Evaluacion Censal de Estudiantes).',
            ],
            [
                'name'        => 'Logros de aprendizaje - Matematica',
                'unit'        => '% nivel satisfactorio',
                'description' => 'Porcentaje de estudiantes con logro satisfactorio en matematica (Evaluacion Censal de Estudiantes).',
            ],
            [
                'name'        => 'Tasa de desercion escolar',
                'unit'        => '%',
                'description' => 'Porcentaje de estudiantes que abandonan el sistema educativo durante el ano escolar.',
            ],
            [
                'name'        => 'Tasa de conclusion primaria',
                'unit'        => '%',
                'description' => 'Porcentaje de poblacion que culmina la educacion primaria en la edad normativa.',
            ],
            [
                'name'        => 'Tasa de conclusion secundaria',
                'unit'        => '%',
                'description' => 'Porcentaje de poblacion que culmina la educacion secundaria en la edad normativa.',
            ],
            [
                'name'        => 'Docentes',
                'unit'        => 'docentes',
                'description' => 'Numero de docentes en ejercicio por distrito.',
            ],
            [
                'name'        => 'Instituciones educativas',
                'unit'        => 'IIEE',
                'description' => 'Numero de instituciones educativas activas.',
            ],
            [
                'name'        => 'Desnutricion cronica infantil',
                'unit'        => '%',
                'description' => 'Porcentaje de ninos menores de 5 anos con desnutricion cronica (fuente MIDIS).',
            ],
            [
                'name'        => 'Pobreza monetaria',
                'unit'        => '%',
                'description' => 'Porcentaje de poblacion en situacion de pobreza monetaria (fuente INEI).',
            ],
        ];

        foreach ($indicators as $indicator) {
            Indicator::query()->updateOrCreate(
                ['name' => $indicator['name']],
                $indicator,
            );
        }
    }
}
