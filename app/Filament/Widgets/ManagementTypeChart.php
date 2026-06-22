<?php

namespace App\Filament\Widgets;

use App\Models\EducationalInstitution;
use Filament\Widgets\ChartWidget;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Dato demografico institucional: cuantas IIEE son de gestion publica
 * (directa o privada/por convenio) frente a privadas particulares.
 */
class ManagementTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Instituciones por tipo de gestión';

    protected static ?string $maxHeight = '320px';

    protected static ?int $sort = 8;

    protected static string $view = 'filament.widgets.chart-widget';

    protected function getData(): array
    {
        $totals = EducationalInstitution::query()
            ->selectRaw('management_type, COUNT(*) as total')
            ->groupBy('management_type')
            ->pluck('total', 'management_type');

        return [
            'datasets' => [
                [
                    'data' => $totals->values()->all(),
                    'backgroundColor' => ['#d97706', '#fbbf24', '#fde68a', '#9ca3af'],
                ],
            ],
            'labels' => $totals->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getSourceLine(): string
    {
        return 'Fuente: ESCALE';
    }
}
