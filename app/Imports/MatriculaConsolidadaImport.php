<?php

namespace App\Imports;

use App\Imports\Concerns\ResolvesDistricts;
use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Importador especializado para los reportes consolidados de matrícula de la
 * UGEL Quispicanchi / ESCALE. Estos archivos suelen traer:
 *
 *   - Varias filas de titulo/metadata antes del encabezado real
 *     (no se asume que la cabecera esta en la fila 1).
 *   - Una fila POR CADA Institucion Educativa (no por distrito), con una
 *     columna "Distrito" y una columna de cantidad de alumnos (el nombre
 *     exacto varia: "Total", "Total de estudiantes matriculados (*)",
 *     "Alumnos (Censo educativo)", etc.).
 *   - Celdas vacias en la columna de alumnos se cuentan como 0 (IE sin
 *     reportar), no como error.
 *   - Opcionalmente una columna "Año"/"Anio"/"Periodo"/"Year" por fila.
 *
 * Comportamiento:
 *   1. Detecta la fila de encabezado real buscando la celda "Distrito"
 *      (se usa la ultima coincidencia, para alinearse con sub-encabezados).
 *   2. Ubica las columnas de Distrito, Total (matriculados) y Año dentro
 *      de esa fila.
 *   3. Recorre las filas de datos, resuelve el distrito de cada IE y
 *      ACUMULA (suma) el total por distrito + año, ya que un distrito
 *      tiene varias IE.
 *   4. El año de cada fila viene de su propia columna si existe; si no,
 *      usa el $year del constructor (seleccionado en el formulario) como
 *      respaldo. Sin columna de año ni respaldo, la fila se reporta como error.
 *   5. Al final, inserta o actualiza en `data_records` un registro por
 *      distrito + año con la suma total.
 */
class MatriculaConsolidadaImport implements SkipsEmptyRows, ToCollection
{
    use Importable;
    use ResolvesDistricts;

    /** @var array<int, string> */
    public array $errors = [];

    public int $imported = 0;

    public int $updated = 0;

    /** Indicador resuelto a partir de $indicatorId. */
    private ?Indicator $indicator = null;

    /**
     * @param  int  $indicatorId  ID del indicador existente al que se asignaran los valores.
     * @param  ?int  $year  Año de respaldo (2022-2026), usado solo en filas que no traigan
     *                       su propia columna "Año"/"Anio"/"Periodo"/"Year". Si se deja en
     *                       null, esas filas sin año propio se reportan como error.
     */
    public function __construct(
        private readonly int $indicatorId,
        private readonly ?int $year = null,
    ) {
        if ($year !== null && ($year < DataRecord::MIN_YEAR || $year > DataRecord::MAX_YEAR)) {
            throw new \InvalidArgumentException(
                "El año {$year} esta fuera del periodo permitido (".DataRecord::MIN_YEAR.'-'.DataRecord::MAX_YEAR.').'
            );
        }
    }

    /**
     * Procesa todas las filas de la hoja activa.
     */
    public function collection(Collection $rows): void
    {
        $this->warmDistrictCache();

        $headerIndex = $this->findHeaderRowIndex($rows);

        if ($headerIndex === null) {
            $this->errors[] = "No se encontro una fila de cabecera con la columna 'Distrito' en el archivo.";

            return;
        }

        $columns = $this->mapColumns($rows->get($headerIndex));

        if ($columns['total'] === null) {
            $this->errors[] = "No se encontro una columna de total de estudiantes matriculados en el archivo.";

            return;
        }

        /** @var array<string, array{district: District, year: int, total: float}> $aggregates */
        $aggregates = [];

        foreach ($rows as $index => $row) {
            if ($index <= $headerIndex) {
                continue;
            }

            $line = $index + 1;

            try {
                $this->accumulateRow($row, $columns, $aggregates);
            } catch (\Throwable $e) {
                $this->errors[] = "Fila {$line}: {$e->getMessage()}";
            }
        }

        $this->persistAggregates($aggregates);
    }

    /**
     * Busca la fila de encabezado real: la ultima fila que contenga una
     * celda exactamente igual a "Distrito" (sin tildes, sin mayusculas).
     */
    private function findHeaderRowIndex(Collection $rows): ?int
    {
        $headerIndex = null;

        foreach ($rows as $index => $row) {
            foreach ($row as $value) {
                if (Str::lower(Str::ascii(trim((string) $value))) === 'distrito') {
                    $headerIndex = $index;
                    break;
                }
            }
        }

        return $headerIndex;
    }

    /**
     * Ubica los indices de columna de Distrito, Total y Año dentro de la
     * fila de encabezado detectada.
     *
     * @return array{distrito: ?int, total: ?int, ano: ?int}
     */
    private function mapColumns(Collection $headerRow): array
    {
        $distritoIdx = null;
        $totalIdx = null;
        $totalFallbackIdx = null;
        $anoIdx = null;

        foreach ($headerRow as $idx => $value) {
            $lower = Str::lower(Str::ascii(trim((string) $value)));

            if ($lower === '') {
                continue;
            }

            if ($distritoIdx === null && $lower === 'distrito') {
                $distritoIdx = $idx;
            }

            if ($totalIdx === null && str_contains($lower, 'total') && str_contains($lower, 'matricul')) {
                $totalIdx = $idx;
            } elseif ($totalIdx === null && str_contains($lower, 'alumnos')) {
                $totalIdx = $idx;
            } elseif ($totalFallbackIdx === null && $lower === 'total') {
                $totalFallbackIdx = $idx;
            }

            if ($anoIdx === null && in_array($lower, ['ano', 'anio', 'periodo', 'year'], true)) {
                $anoIdx = $idx;
            }
        }

        return [
            'distrito' => $distritoIdx,
            'total'    => $totalIdx ?? $totalFallbackIdx,
            'ano'      => $anoIdx,
        ];
    }

    /**
     * Procesa una fila de datos (una IE) y acumula su total en $aggregates,
     * agrupado por distrito + año.
     *
     * @param  array{distrito: ?int, total: ?int, ano: ?int}  $columns
     * @param  array<string, array{district: District, year: int, total: float}>  $aggregates
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    private function accumulateRow(Collection $row, array $columns, array &$aggregates): void
    {
        if ($columns['distrito'] === null) {
            throw new \RuntimeException("No se encontro la columna 'Distrito' en el archivo.");
        }

        $districtName = trim((string) ($row[$columns['distrito']] ?? ''));

        if ($districtName === '') {
            // Fila vacia o sub-cabecera de columnas agrupadas (sin distrito) → se omite silenciosamente
            return;
        }

        // NO procesar filas que sean subtotales/grandes totales
        $lowerDistrict = Str::lower($districtName);
        if (str_contains($lowerDistrict, 'subtotal') || str_contains($lowerDistrict, 'total') || str_contains($lowerDistrict, 'gran')) {
            return;
        }

        $total = $row[$columns['total']] ?? null;

        // Celda vacia (sin reportar) → se cuenta como 0, no es un error
        if ($total === null || trim((string) $total) === '') {
            $total = 0;
        } elseif (! is_numeric($total)) {
            throw new \InvalidArgumentException(
                "Columna de total con valor no numerico ('{$total}') para el distrito '{$districtName}'."
            );
        }

        $year = $this->resolveRowYear($row, $columns['ano']);

        $district = $this->resolveDistrict($districtName);

        $key = $district->id.'|'.$year;

        $aggregates[$key] ??= ['district' => $district, 'year' => $year, 'total' => 0.0];
        $aggregates[$key]['total'] += (float) $total;
    }

    /**
     * Resuelve el año de la fila a partir de la columna detectada (si existe).
     * Si la columna no existe o esta vacia, usa el año seleccionado en el formulario.
     *
     * @throws \InvalidArgumentException si el año de la fila esta fuera del periodo permitido,
     *                                    o si no hay ni columna de año ni año de respaldo.
     */
    private function resolveRowYear(Collection $row, ?int $anoIdx): int
    {
        $rawYear = $anoIdx !== null ? ($row[$anoIdx] ?? null) : null;

        if ($rawYear === null || trim((string) $rawYear) === '') {
            if ($this->year === null) {
                throw new \InvalidArgumentException(
                    'no trae columna de año y no se eligio un año de respaldo en el formulario.'
                );
            }

            return $this->year;
        }

        if (! is_numeric($rawYear)) {
            throw new \InvalidArgumentException("valor de año '{$rawYear}' no es numerico.");
        }

        $year = (int) $rawYear;

        if ($year < DataRecord::MIN_YEAR || $year > DataRecord::MAX_YEAR) {
            throw new \InvalidArgumentException(
                "el año {$year} esta fuera del periodo permitido (".DataRecord::MIN_YEAR.'-'.DataRecord::MAX_YEAR.').'
            );
        }

        return $year;
    }

    /**
     * Inserta o actualiza en `data_records` un registro por cada distrito + año
     * acumulado, con la suma total de sus IE.
     *
     * @param  array<string, array{district: District, year: int, total: float}>  $aggregates
     */
    private function persistAggregates(array $aggregates): void
    {
        if ($aggregates === []) {
            return;
        }

        $indicator = $this->resolveIndicator();

        foreach ($aggregates as $aggregate) {
            $district = $aggregate['district'];
            $year = $aggregate['year'];

            $exists = DataRecord::query()
                ->where('district_id', $district->id)
                ->where('indicator_id', $indicator->id)
                ->where('year', $year)
                ->exists();

            DataRecord::query()->updateOrCreate(
                [
                    'district_id'  => $district->id,
                    'indicator_id' => $indicator->id,
                    'year'         => $year,
                ],
                [
                    'value'  => $aggregate['total'],
                    'source' => 'UGEL Quispicanchi / ESCALE',
                ],
            );

            $exists ? $this->updated++ : $this->imported++;
        }
    }

    /**
     * Resuelve el Indicator existente a partir de $indicatorId.
     *
     * @throws \RuntimeException si el indicador no existe.
     */
    private function resolveIndicator(): Indicator
    {
        if ($this->indicator === null) {
            $this->indicator = Indicator::query()->find($this->indicatorId);

            if ($this->indicator === null) {
                throw new \RuntimeException("No existe el indicador con ID {$this->indicatorId}.");
            }
        }

        return $this->indicator;
    }
}
