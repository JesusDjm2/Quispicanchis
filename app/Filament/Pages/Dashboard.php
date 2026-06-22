<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DataOverviewStats;
use App\Filament\Widgets\DistrictComparisonChart;
use App\Filament\Widgets\IndicatorCompletenessTable;
use App\Filament\Widgets\IndicatorTrendChart;
use App\Filament\Widgets\InstitutionCensusOverviewStats;
use App\Filament\Widgets\ManagementTypeChart;
use App\Filament\Widgets\StudentsByDistrictChart;
use App\Filament\Widgets\StudentsByLevelChart;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Dashboard del panel agrupado en dos secciones (en vez de la lista plana
 * default de Filament), para que el cumplimiento del contrato se vea de
 * un vistazo: indicadores del PEL Quispicanchi por un lado, censo ESCALE
 * de instituciones educativas por otro.
 */
class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    /** @return array<class-string> */
    public function getIndicatorWidgets(): array
    {
        return [
            DataOverviewStats::class,
            IndicatorCompletenessTable::class,
            IndicatorTrendChart::class,
            DistrictComparisonChart::class,
        ];
    }

    /** @return array<class-string> */
    public function getCensusWidgets(): array
    {
        return [
            InstitutionCensusOverviewStats::class,
            StudentsByDistrictChart::class,
            StudentsByLevelChart::class,
            ManagementTypeChart::class,
        ];
    }
}
