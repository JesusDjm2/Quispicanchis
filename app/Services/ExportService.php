<?php

namespace App\Services;

use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Language;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Genera el reporte en Microsoft Word (.docx) del proyecto
 * "PEL Quispicanchi al 2036", con la estructura rigurosa exigida por
 * el contrato para el entregable oficial.
 *
 * Por cada indicador solicitado:
 *   a) Titulo del Indicador (Estilo Encabezado destacado).
 *   b) Tabla de Progresion Historica:
 *        Columnas: [Distrito | Ano 2022 | Ano 2023 | Ano 2024 | Ano 2025 | Ano 2026]
 *        con los valores de cada distrito atendido por UGEL Quispicanchi.
 *   c) Placeholder visible: "[Insertar Grafico de Tendencia Chart.js Aqui]".
 *   d) Leyenda obligatoria: "Fuente: UGEL Quispicanchi / ESCALE | Elaboracion: Edutalento"
 */
class ExportService
{
    /** Periodo oficial del contrato. */
    private const DEFAULT_YEARS = [2022, 2023, 2024, 2025, 2026];

    /** Etiqueta de grÃ¡fico placeholder. */
    private const CHART_PLACEHOLDER = '[Insertar Grafico de Tendencia Chart.js Aqui]';

    /** Fuente por defecto del reporte. */
    private const SOURCE_TEXT = 'Fuente: UGEL Quispicanchi / ESCALE';

    /** Leyenda obligatoria del contrato. */
    private const LEGEND = 'Elaboracion: Edutalento';

    /**
     * Genera un documento PhpWord con la estructura completa del reporte.
     *
     * @param  int  $indicatorId  ID del indicador a reportar.
     * @param  array<int, int>|null  $years  Anos a incluir (default: 2022-2026).
     * @return PhpWord
     */
    public function generateWord(int $indicatorId, ?array $years = null): PhpWord
    {
        $years ??= self::DEFAULT_YEARS;
        $indicator = Indicator::findOrFail($indicatorId);
        $districts = District::orderBy('name')->get();

        // Configurar documento
        $phpWord = new PhpWord;
        $phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        // Estilos del documento
        $this->registerStyles($phpWord);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginLeft'  => Converter::cmToTwip(2),
            'marginRight' => Converter::cmToTwip(2),
            'marginTop'   => Converter::cmToTwip(2),
            'marginBottom'=> Converter::cmToTwip(2),
        ]);

        // --- ENCABEZADO DEL REPORTE ---
        $section->addTitle('PEL Quispicanchi al 2036', 1);
        $section->addText(
            'Reporte de Progresion Historica de Indicadores Educativos',
            ['italic' => true, 'size' => 12, 'color' => '555555'],
        );
        $section->addTextBreak(1);

        // --- CUERPO DEL REPORTE: INDICADOR ---
        $this->addIndicatorSection($section, $indicator, $districts, $years);

        return $phpWord;
    }

    /**
     * Genera y guarda el reporte Word en una ruta del sistema de archivos.
     *
     * @param  int  $indicatorId  ID del indicador.
     * @param  string  $path  Ruta de salida (ej. storage_path('app/reportes/reporte.docx')) .
     * @param  array<int, int>|null  $years
     * @return string La ruta del archivo generado.
     */
    public function saveWord(int $indicatorId, string $path, ?array $years = null): string
    {
        $phpWord = $this->generateWord($indicatorId, $years);
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    /**
     * Genera y descarga el reporte como respuesta HTTP.
     *
     * @param  int  $indicatorId
     * @param  string  $filename  Nombre del archivo descargado.
     * @param  array<int, int>|null  $years
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadWord(int $indicatorId, string $filename, ?array $years = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $phpWord = $this->generateWord($indicatorId, $years);

        return response()->streamDownload(function () use ($phpWord): void {
            IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        }, $filename);
    }

    // ========================================================================
    //  METODOS PRIVADOS
    // ========================================================================

    /**
     * Registra estilos reutilizables en el documento.
     */
    private function registerStyles(PhpWord $phpWord): void
    {
        // Titulo principal
        $phpWord->addTitleStyle(1, [
            'bold'   => true,
            'size'   => 18,
            'color'  => '1F4E79',
            'spaceAfter' => 200,
        ]);

        // Titulo de indicador
        $phpWord->addTitleStyle(2, [
            'bold'   => true,
            'size'   => 14,
            'color'  => '2E75B6',
            'spaceBefore' => 300,
            'spaceAfter'  => 150,
        ]);

        // Estilo para la leyenda
        $phpWord->addFontStyle('legendStyle', [
            'italic' => true,
            'size'   => 8,
            'color'  => '666666',
        ]);

        // Estilo para el placeholder del grafico
        $phpWord->addParagraphStyle('chartPlaceholder', [
            'align'      => 'center',
            'spaceBefore' => 200,
            'spaceAfter'  => 200,
            'borderSize'  => 6,
            'borderColor' => 'CCCCCC',
            'paddingTop'  => 100,
            'paddingBottom' => 100,
        ]);
    }

    /**
     * Agrega la seccion completa de un indicador al documento:
     *   a) Titulo del Indicador (Encabezado nivel 2)
     *   b) Tabla de Progresion Historica [Distrito | 2022 | 2023 | 2024 | 2025 | 2026]
     *   c) Placeholder del grafico
     *   d) Leyenda "Fuente: ... | Elaboracion: Edutalento"
     *
     * @param  \PhpOffice\PhpWord\Element\Section  $section
     * @param  Indicator  $indicator
     * @param  Collection<int, District>  $districts
     * @param  array<int, int>  $years
     */
    private function addIndicatorSection(
        \PhpOffice\PhpWord\Element\Section $section,
        Indicator $indicator,
        Collection $districts,
        array $years,
    ): void {
        // --- a) TITULO DEL INDICADOR ---
        $title = $indicator->name;
        if ($indicator->unit) {
            $title .= ' (' . $indicator->unit . ')';
        }
        $section->addTitle($title, 2);

        // Descripcion del indicador
        if ($indicator->description) {
            $section->addText(
                $indicator->description,
                ['italic' => true, 'size' => 10, 'color' => '555555'],
            );
        }
        $section->addTextBreak(1);

        // Cargar registros de una sola vez
        $recordsByDistrict = DataRecord::query()
            ->where('indicator_id', $indicator->id)
            ->whereIn('year', $years)
            ->get()
            ->groupBy('district_id');

        // --- b) TABLA DE PROGRESION HISTORICA ---
        $this->addHistoricalTable($section, $districts, $years, $recordsByDistrict);

        $section->addTextBreak(1);

        // --- c) PLACEHOLDER DEL GRAFICO ---
        $this->addChartPlaceholder($section, $indicator);

        $section->addTextBreak(1);

        // --- d) LEYENDA OBLIGATORIA ---
        $this->addLegend($section);
    }

    /**
     * Construye la tabla de progresion historica.
     *
     * Columnas fijas: Distrito | AÃ±o 2022 | AÃ±o 2023 | AÃ±o 2024 | AÃ±o 2025 | AÃ±o 2026
     * Filas: los distritos en orden alfabetico. Celdas sin dato muestran "S/D".
     *
     * @param  \PhpOffice\PhpWord\Element\Section  $section
     * @param  Collection<int, District>  $districts
     * @param  array<int, int>  $years
     * @param  Collection<int, Collection<int, DataRecord>>  $recordsByDistrict
     */
    private function addHistoricalTable(
        \PhpOffice\PhpWord\Element\Section $section,
        Collection $districts,
        array $years,
        Collection $recordsByDistrict,
    ): void {
        // Estilos de la tabla
        $tableStyle = [
            'borderSize'    => 6,
            'borderColor'   => '999999',
            'cellMargin'    => 60,
            'alignment'     => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ];

        $firstRowStyle = [
            'bgColor' => '1F4E79',
        ];

        $headerFont = [
            'bold'  => true,
            'size'  => 9,
            'color' => 'FFFFFF',
        ];

        $cellFont = [
            'size' => 10,
        ];

        $cellStyleCenter = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];
        $cellStyleLeft   = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT];

        // Anchos de columna (en twips): Distrito mas ancho, aÃ±os iguales
        $colWidths = [2200];
        foreach ($years as $i => $year) {
            $colWidths[] = 1300;
        }

        $table = $section->addTable($tableStyle);

        // --- CABECERA ---
        $table->addRow(null, $firstRowStyle);
        $table->addCell($colWidths[0], $cellStyleCenter)->addText('Distrito', $headerFont);
        foreach ($years as $year) {
            $table->addCell(1300, $cellStyleCenter)->addText('Ano ' . $year, $headerFont);
        }

        // --- FILAS DE DISTRITOS ---
        $rowIndex = 0;
        foreach ($districts as $district) {
            $rowBgColor = ($rowIndex % 2 === 0) ? ['bgColor' => 'F2F7FB'] : ['bgColor' => 'FFFFFF'];
            $table->addRow(null, $rowBgColor);

            // Celda del nombre del distrito
            $table->addCell($colWidths[0], $cellStyleLeft)->addText($district->name, [
                'bold' => true,
                'size' => 10,
            ]);

            $districtRecords = $recordsByDistrict->get($district->id, collect());

            // Celdas de cada ano
            foreach ($years as $year) {
                $record = $districtRecords->firstWhere('year', $year);
                $valueText = $record
                    ? number_format((float) $record->value, 2, '.', ',')
                    : 'S/D';

                $valueStyle = $record
                    ? $cellFont
                    : array_merge($cellFont, ['color' => 'CC0000', 'italic' => true]);

                $table->addCell(1300, $cellStyleCenter)->addText($valueText, $valueStyle);
            }

            $rowIndex++;
        }
    }

    /**
     * Agrega el placeholder visible para el grafico de tendencia.
     *
     * @param  \PhpOffice\PhpWord\Element\Section  $section
     * @param  Indicator  $indicator
     */
    private function addChartPlaceholder(
        \PhpOffice\PhpWord\Element\Section $section,
        Indicator $indicator,
    ): void {
        // Texto informativo antes del placeholder
        $section->addText(
            'Grafico de Tendencia Provincial: ' . $indicator->name,
            ['bold' => true, 'size' => 11, 'color' => '2E75B6'],
        );

        // Placeholder en un recuadro con borde punteado
        $section->addText(
            self::CHART_PLACEHOLDER,
            ['size' => 12, 'color' => '888888', 'italic' => true],
            'chartPlaceholder',
        );

        // Texto de ayuda
        $section->addText(
            'NOTA: Este grafico debe generarse desde el panel Filament (widget IndicatorTrendChart)'
            . ' utilizando Chart.js y pegarse manualmente en esta ubicacion antes de la entrega final.',
            ['size' => 8, 'color' => '999999', 'italic' => true],
        );
    }

    /**
     * Agrega la leyenda obligatoria al pie de cada seccion de indicador.
     *
     * @param  \PhpOffice\PhpWord\Element\Section  $section
     */
    private function addLegend(\PhpOffice\PhpWord\Element\Section $section): void
    {
        $section->addText(self::SOURCE_TEXT, 'legendStyle');
        $section->addText(self::LEGEND, 'legendStyle');
    }
}
