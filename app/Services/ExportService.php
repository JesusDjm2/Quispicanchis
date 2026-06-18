<?php

namespace App\Services;

use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Genera el reporte Word oficial del proyecto "PEL Quispicanchi al 2036".
 * Por cada indicador seleccionado respeta el orden exigido por el contrato:
 * Titulo del indicador + Tabla (progresion historica 2022-2026) + Grafico,
 * y cierra siempre con Fuente + la leyenda "Elaboración: Edutalento".
 */
class ExportService
{
    private const LEGEND = 'Elaboración: Edutalento';

    /**
     * @param  Collection<int, Indicator>  $indicators
     * @param  array<int, int>|null  $years
     */
    public function generateWord(Collection $indicators, ?array $years = null): PhpWord
    {
        $years ??= DataRecord::availableYears();
        $districts = District::orderBy('name')->get();

        $this->assertNoMissingData($indicators, $districts, $years);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection();

        foreach ($indicators as $indicator) {
            $this->addIndicatorSection($section, $indicator, $districts, $years);
        }

        return $phpWord;
    }

    /**
     * @param  Collection<int, Indicator>  $indicators
     * @param  array<int, int>|null  $years
     */
    public function saveWord(Collection $indicators, string $path, ?array $years = null): string
    {
        $phpWord = $this->generateWord($indicators, $years);

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    /**
     * @param  Collection<int, District>  $districts
     * @param  array<int, int>  $years
     */
    private function addIndicatorSection(
        \PhpOffice\PhpWord\Element\Section $section,
        Indicator $indicator,
        Collection $districts,
        array $years,
    ): void {
        $title = $indicator->name.($indicator->unit ? ' ('.$indicator->unit.')' : '');
        $section->addTitle($title, 1);

        $recordsByDistrict = DataRecord::query()
            ->where('indicator_id', $indicator->id)
            ->whereIn('year', $years)
            ->get()
            ->groupBy('district_id');

        $sourcesUsed = $this->addHistoricalTable($section, $districts, $years, $recordsByDistrict);

        $section->addTextBreak();
        $this->addTrendChart($section, $indicator, $years, $recordsByDistrict);

        $section->addTextBreak();
        $section->addText(
            'Fuente: '.($sourcesUsed === [] ? 'N/D' : implode(', ', array_keys($sourcesUsed))),
            ['size' => 9],
        );
        $section->addText(self::LEGEND, ['italic' => true, 'size' => 9, 'color' => '555555']);
        $section->addTextBreak(2);
    }

    /**
     * @param  Collection<int, District>  $districts
     * @param  array<int, int>  $years
     * @param  Collection<int, Collection<int, DataRecord>>  $recordsByDistrict
     * @return array<string, true>
     */
    private function addHistoricalTable(
        \PhpOffice\PhpWord\Element\Section $section,
        Collection $districts,
        array $years,
        Collection $recordsByDistrict,
    ): array {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ]);

        $headerStyle = ['bgColor' => 'DCE6F1'];
        $headerFont = ['bold' => true];

        $table->addRow();
        $table->addCell(2500, $headerStyle)->addText('Distrito', $headerFont);
        foreach ($years as $year) {
            $table->addCell(1200, $headerStyle)->addText((string) $year, $headerFont);
        }

        $sourcesUsed = [];

        foreach ($districts as $district) {
            $table->addRow();
            $table->addCell(2500)->addText($district->name);

            $districtRecords = $recordsByDistrict->get($district->id, collect());

            foreach ($years as $year) {
                $record = $districtRecords->firstWhere('year', $year);
                $table->addCell(1200)->addText($record ? number_format((float) $record->value, 2) : 'S/D');

                if ($record) {
                    $sourcesUsed[$record->source] = true;
                }
            }
        }

        return $sourcesUsed;
    }

    /**
     * @param  array<int, int>  $years
     * @param  Collection<int, Collection<int, DataRecord>>  $recordsByDistrict
     */
    private function addTrendChart(
        \PhpOffice\PhpWord\Element\Section $section,
        Indicator $indicator,
        array $years,
        Collection $recordsByDistrict,
    ): void {
        $allRecords = $recordsByDistrict->flatten();

        $series = array_map(
            fn (int $year): float => round((float) $allRecords->where('year', $year)->avg('value'), 2),
            $years,
        );

        $section->addChart(
            'line',
            array_map('strval', $years),
            $series,
            [
                'width' => Converter::cmToEmu(14),
                'height' => Converter::cmToEmu(8),
                'title' => 'Promedio provincial - '.$indicator->name,
            ],
        );
    }

    /**
     * @param  Collection<int, Indicator>  $indicators
     * @param  Collection<int, District>  $districts
     * @param  array<int, int>  $years
     */
    private function assertNoMissingData(Collection $indicators, Collection $districts, array $years): void
    {
        $missing = [];

        foreach ($indicators as $indicator) {
            $present = DataRecord::query()
                ->where('indicator_id', $indicator->id)
                ->whereIn('year', $years)
                ->get()
                ->map(fn (DataRecord $record) => $record->district_id.'-'.$record->year)
                ->flip();

            foreach ($districts as $district) {
                foreach ($years as $year) {
                    if (! isset($present[$district->id.'-'.$year])) {
                        $missing[] = "{$indicator->name} / {$district->name} / {$year}";
                    }
                }
            }
        }

        if ($missing !== []) {
            throw new \RuntimeException(
                "No se puede generar el reporte: faltan datos para los siguientes registros:\n".implode("\n", $missing)
            );
        }
    }
}
