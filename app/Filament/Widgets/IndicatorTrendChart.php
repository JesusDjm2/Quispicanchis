<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ResolvesIndicatorSourceLine;
use App\Models\DataRecord;
use App\Models\Indicator;
use Filament\Widgets\ChartWidget;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Visualiza la tendencia 2022-2026 (promedio provincial) de un indicador
 * seleccionado, usando Chart.js a traves de los widgets de Filament.
 */
class IndicatorTrendChart extends ChartWidget
{
    use ResolvesIndicatorSourceLine;

    protected static ?string $heading = 'Tendencia provincial 2022-2026';

    protected static ?string $maxHeight = '320px';

    protected static ?int $sort = 3;

    protected static string $view = 'filament.widgets.chart-widget';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        return Indicator::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    protected function getData(): array
    {
        $indicatorId = $this->filter ?? Indicator::query()->orderBy('name')->value('id');
        $indicator = Indicator::find($indicatorId);
        $years = DataRecord::availableYears();

        $averagesByYear = DataRecord::query()
            ->where('indicator_id', $indicatorId)
            ->whereIn('year', $years)
            ->get()
            ->groupBy('year')
            ->map(fn ($records) => round((float) $records->avg('value'), 2));

        return [
            'datasets' => [
                [
                    'label' => $indicator?->name ?? 'Indicador',
                    'data' => collect($years)->map(fn (int $year) => $averagesByYear->get($year, 0))->all(),
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $years,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
