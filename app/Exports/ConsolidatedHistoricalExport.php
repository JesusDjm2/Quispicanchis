<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Unifica en un solo archivo Excel la progresion historica de varios
 * indicadores a la vez: una pestaña por indicador, cada una con la misma
 * estructura que HistoricalProgressionExport (tabla Distrito x Año, fuente,
 * leyenda "Elaboración: Edutalento" y referencias APA 7).
 */
class ConsolidatedHistoricalExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * @param  array<int, int>  $indicatorIds  Indicadores a incluir, uno por pestaña.
     * @param  array<int, int>|null  $years  Años a incluir (default: 2022-2026).
     */
    public function __construct(
        private readonly array $indicatorIds,
        private readonly ?array $years = null,
    ) {}

    /**
     * @return array<int, HistoricalProgressionExport>
     */
    public function sheets(): array
    {
        return array_map(
            fn (int $indicatorId) => new HistoricalProgressionExport($indicatorId, $this->years),
            $this->indicatorIds,
        );
    }
}
