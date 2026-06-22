<?php

namespace App\Filament\Widgets;

use App\Models\InstitutionLevelCensus;
use Filament\Widgets\ChartWidget;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Distribucion de alumnos por nivel educativo amplio (Inicial, Primaria,
 * Secundaria, Básica Especial, Básica Alternativa, Técnico Productiva),
 * agrupando las variantes de "Nivel / Modalidad" del censo ESCALE.
 */
class StudentsByLevelChart extends ChartWidget
{
    protected static ?string $heading = 'Alumnos por nivel educativo (censo ESCALE)';

    protected static ?string $maxHeight = '320px';

    protected static ?int $sort = 7;

    protected static string $view = 'filament.widgets.chart-widget';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        $years = InstitutionLevelCensus::query()
            ->distinct()
            ->orderByDesc('census_year')
            ->pluck('census_year');

        return $years->isEmpty() ? null : $years->mapWithKeys(fn (int $year) => [$year => (string) $year])->all();
    }

    protected function getData(): array
    {
        $year = $this->filter ?? InstitutionLevelCensus::query()->max('census_year');

        $totalsByLevel = InstitutionLevelCensus::query()
            ->where('census_year', $year)
            ->get()
            ->groupBy(fn (InstitutionLevelCensus $census) => $census->levelGroup())
            ->map(fn ($group) => (int) $group->sum('students'))
            ->sortDesc();

        return [
            'datasets' => [
                [
                    'label' => "Alumnos ({$year})",
                    'data' => $totalsByLevel->values()->all(),
                    'backgroundColor' => [
                        '#d97706', '#f59e0b', '#fbbf24', '#fcd34d', '#fde68a', '#fef3c7',
                    ],
                ],
            ],
            'labels' => $totalsByLevel->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getSourceLine(): string
    {
        return 'Fuente: ESCALE';
    }
}
