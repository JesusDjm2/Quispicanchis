<?php

namespace App\Filament\Widgets;

use App\Models\EducationalInstitution;
use App\Models\InstitutionLevelCensus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Resumen del ultimo censo ESCALE cargado: total de instituciones educativas,
 * alumnos, docentes, secciones y el ratio alumno/docente de la provincia.
 */
class InstitutionCensusOverviewStats extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $latestYear = InstitutionLevelCensus::query()->max('census_year');

        if ($latestYear === null) {
            return [
                Stat::make('Censo ESCALE', 'Sin datos')
                    ->description('Importa el padron de Instituciones desde "Instituciones educativas"')
                    ->color('gray'),
            ];
        }

        $census = InstitutionLevelCensus::query()->where('census_year', $latestYear);

        $totalInstitutions = EducationalInstitution::query()
            ->whereHas('levelCensuses', fn ($q) => $q->where('census_year', $latestYear))
            ->count();
        $totalStudents = (clone $census)->sum('students');
        $totalTeachers = (clone $census)->sum('teachers');
        $totalSections = (clone $census)->sum('sections');
        $ratio = $totalTeachers > 0 ? round($totalStudents / $totalTeachers, 1) : 0;

        return [
            Stat::make('Instituciones educativas', $totalInstitutions)
                ->description("Censo ESCALE {$latestYear}")
                ->color('gray'),
            Stat::make('Alumnos', number_format($totalStudents))
                ->color('success'),
            Stat::make('Docentes', number_format($totalTeachers))
                ->color('success'),
            Stat::make('Secciones', number_format($totalSections))
                ->color('success'),
            Stat::make('Ratio alumno/docente', $ratio)
                ->color(match (true) {
                    $ratio > 0 && $ratio <= 20 => 'success',
                    $ratio <= 30 => 'warning',
                    default => 'danger',
                }),
        ];
    }
}
