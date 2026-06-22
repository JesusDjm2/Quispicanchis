<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\DataRecord;
use App\Models\Indicator;
use App\Services\ApaReferenceService;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Para widgets con selector de indicador (`public ?string $filter`): calcula
 * la linea "Fuente: ..." a partir de las fuentes realmente usadas por el
 * indicador filtrado, igual que en el reporte Word/Excel (ApaReferenceService).
 */
trait ResolvesIndicatorSourceLine
{
    protected function getSourceLine(): string
    {
        $indicatorId = $this->filter ?? Indicator::query()->orderBy('name')->value('id');

        if ($indicatorId === null) {
            return 'Fuente: UGEL Quispicanchi / ESCALE / INEI / MIDIS';
        }

        $records = DataRecord::query()->where('indicator_id', $indicatorId)->get();

        return app(ApaReferenceService::class)->sourceLine($records);
    }
}
