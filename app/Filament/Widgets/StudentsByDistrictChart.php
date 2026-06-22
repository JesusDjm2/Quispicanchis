<?php

namespace App\Filament\Widgets;

use App\Models\District;
use App\Models\InstitutionLevelCensus;
use Filament\Widgets\ChartWidget;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Alumnos del censo ESCALE agrupados por distrito, para el año censal
 * seleccionado (por defecto, el mas reciente cargado).
 */
class StudentsByDistrictChart extends ChartWidget
{
    protected static ?string $heading = 'Alumnos por distrito (censo ESCALE)';

    protected static ?string $maxHeight = '320px';

    protected static ?int $sort = 6;

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

        $studentsByDistrict = InstitutionLevelCensus::query()
            ->where('census_year', $year)
            ->join('educational_institutions', 'educational_institutions.id', '=', 'institution_level_census.educational_institution_id')
            ->selectRaw('educational_institutions.district_id, SUM(institution_level_census.students) as total')
            ->groupBy('educational_institutions.district_id')
            ->pluck('total', 'district_id');

        // Censo ESCALE de UGEL Quispicanchi: Lucre y Oropesa no aparecen aqui.
        $districts = District::query()->ugelManaged()->orderBy('name')->get();

        return [
            'datasets' => [
                [
                    'label' => "Alumnos ({$year})",
                    'data' => $districts->map(fn (District $district) => (int) ($studentsByDistrict->get($district->id) ?? 0))->all(),
                    'backgroundColor' => '#d97706',
                ],
            ],
            'labels' => $districts->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getSourceLine(): string
    {
        return 'Fuente: ESCALE';
    }
}
