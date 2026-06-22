<?php

namespace App\Exports;

use App\Models\DataRecord;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Exportacion masiva a Microsoft Excel de los registros de datos del PEL
 * Quispicanchi, incluyendo la leyenda "Elaboración: Edutalento" exigida por
 * el contrato.
 */
class DataRecordsExport implements FromQuery, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private readonly ?int $districtId = null,
        private readonly ?int $indicatorId = null,
    ) {}

    public function query(): Builder
    {
        return DataRecord::query()
            ->with(['district', 'indicator'])
            ->when($this->districtId, fn (Builder $query) => $query->where('district_id', $this->districtId))
            ->when($this->indicatorId, fn (Builder $query) => $query->where('indicator_id', $this->indicatorId))
            ->orderBy('district_id')
            ->orderBy('indicator_id')
            ->orderBy('year');
    }

    public function headings(): array
    {
        return ['Distrito', 'Indicador', 'Unidad', 'Año', 'Valor', 'Fuente', 'Fecha de carga'];
    }

    public function map(mixed $record): array
    {
        /** @var DataRecord $record */
        return [
            $record->district->name,
            $record->indicator->name,
            $record->indicator->unit,
            $record->year,
            (float) $record->value,
            $record->source,
            $record->created_at?->format('d/m/Y H:i'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow() + 2;

                $sheet->setCellValue("A{$lastRow}", 'Fuente: UGEL, ESCALE, INEI, MIDIS');
                $sheet->setCellValue('A'.($lastRow + 1), 'Elaboración: Edutalento');
                $sheet->getStyle('A'.($lastRow + 1))->getFont()->setItalic(true);
            },
        ];
    }
}
