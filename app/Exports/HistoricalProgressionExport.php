<?php

namespace App\Exports;

use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use App\Services\ApaReferenceService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Exporta a Excel la misma tabla de progresion historica 2022-2026 por
 * distrito que el reporte Word (ExportService), con su titulo, fuente,
 * leyenda "Elaboración: Edutalento" y referencias APA 7 al pie.
 */
class HistoricalProgressionExport implements FromArray, WithEvents, WithTitle
{
    use Exportable;

    /** @var array<int, int> */
    private array $years;

    /** Fila (1-based) donde inicia la cabecera "Distrito | Año ...". */
    private int $headerRow;

    /** Fila (1-based) donde termina la tabla de distritos. */
    private int $lastDataRow;

    /** @param  array<int, int>|null  $years */
    public function __construct(
        private readonly int $indicatorId,
        ?array $years = null,
    ) {
        $this->years = $years ?? DataRecord::availableYears();
    }

    public function array(): array
    {
        $indicator = Indicator::findOrFail($this->indicatorId);
        $districts = District::orderBy('name')->get();

        $records = DataRecord::query()
            ->where('indicator_id', $indicator->id)
            ->whereIn('year', $this->years)
            ->get();
        $recordsByDistrict = $records->groupBy('district_id');

        $title = $indicator->name.($indicator->unit ? " ({$indicator->unit})" : '');

        $rows = [
            [$title],
            [],
        ];

        $this->headerRow = count($rows) + 1;
        $rows[] = array_merge(['Distrito'], array_map(fn (int $year) => "Año {$year}", $this->years));

        foreach ($districts as $district) {
            $districtRecords = $recordsByDistrict->get($district->id, collect());

            $row = [$district->name];
            foreach ($this->years as $year) {
                $record = $districtRecords->firstWhere('year', $year);
                $row[] = $record ? (float) $record->value : 'S/D';
            }
            $rows[] = $row;
        }

        $this->lastDataRow = count($rows);

        $rows[] = [];
        $rows[] = [app(ApaReferenceService::class)->sourceLine($records)];
        $rows[] = ['Elaboración: Edutalento'];

        $references = app(ApaReferenceService::class)->forIndicator($indicator, $this->years);

        if ($references !== []) {
            $rows[] = [];
            $rows[] = ['Referencias bibliográficas (APA 7.ª ed.)'];

            foreach ($references as $reference) {
                $rows[] = [$reference];
            }
        }

        return $rows;
    }

    public function title(): string
    {
        $indicator = Indicator::find($this->indicatorId);

        return Str::limit($indicator?->name ?? 'Indicador', 31, '');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells("A1:{$sheet->getHighestColumn()}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

                $headerRange = "A{$this->headerRow}:{$sheet->getHighestColumn()}{$this->headerRow}";
                $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF')
                );
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('1F4E79');
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getColumnDimension('A')->setWidth(28);
                foreach (range('B', $sheet->getHighestColumn()) as $column) {
                    $sheet->getColumnDimension($column)->setWidth(14);
                }

                foreach (range($this->headerRow + 1, $this->lastDataRow) as $row) {
                    $sheet->getStyle("B{$row}:{$sheet->getHighestColumn()}{$row}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getStyle("A".($this->lastDataRow + 2).":A".($this->lastDataRow + 3))
                    ->getFont()->setItalic(true)->setSize(9);
            },
        ];
    }
}
