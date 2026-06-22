<?php

namespace App\Filament\Widgets;

use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Filament\Widgets\ChartWidget;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Compara, para el ultimo año disponible, el valor de un indicador entre
 * los distritos de Quispicanchi.
 */
class DistrictComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Comparativo por distrito (último año)';

    protected static ?string $maxHeight = '320px';

    protected static ?int $sort = 4;

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        return Indicator::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    protected function getData(): array
    {
        $indicatorId = $this->filter ?? Indicator::query()->orderBy('name')->value('id');
        $indicator = Indicator::find($indicatorId);

        $latestYear = DataRecord::query()
            ->where('indicator_id', $indicatorId)
            ->max('year') ?? DataRecord::MAX_YEAR;

        $valuesByDistrict = DataRecord::query()
            ->where('indicator_id', $indicatorId)
            ->where('year', $latestYear)
            ->get()
            ->keyBy('district_id');

        $districts = District::query()->orderBy('name')->get();

        return [
            'datasets' => [
                [
                    'label' => ($indicator?->name ?? 'Indicador')." ({$latestYear})",
                    'data' => $districts->map(fn (District $district) => (float) ($valuesByDistrict->get($district->id)?->value ?? 0))->all(),
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
}
