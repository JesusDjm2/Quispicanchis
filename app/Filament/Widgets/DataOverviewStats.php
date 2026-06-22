<?php

namespace App\Filament\Widgets;

use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Resumen general de la base de datos: totales y completitud global,
 * para ubicar de un vistazo el estado de la informacion cargada.
 */
class DataOverviewStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRecords = DataRecord::count();
        $totalDistricts = District::count();
        $totalIndicators = Indicator::count();
        $recentCount = DataRecord::where('created_at', '>=', now()->subDays(7))->count();

        $expectedTotal = $totalDistricts * count(DataRecord::availableYears()) * $totalIndicators;
        $completeness = $expectedTotal > 0 ? round($totalRecords / $expectedTotal * 100, 1) : 0.0;

        return [
            Stat::make('Registros totales', $totalRecords)
                ->description("{$recentCount} cargados en los últimos 7 días")
                ->color('success'),
            Stat::make('Distritos', $totalDistricts)
                ->color('gray'),
            Stat::make('Indicadores', $totalIndicators)
                ->color('gray'),
            Stat::make('Completitud global', "{$completeness}%")
                ->description("{$totalRecords} de {$expectedTotal} registros esperados")
                ->color(match (true) {
                    $completeness >= 80 => 'success',
                    $completeness >= 40 => 'warning',
                    default => 'danger',
                }),
        ];
    }
}
