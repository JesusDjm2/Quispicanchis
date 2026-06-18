<?php

namespace App\Imports;

use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Unifica la data dispersa entregada en Excel (UGEL, ESCALE, INEI, MIDIS).
 * Columnas esperadas en la primera fila (no distingue mayusculas/tildes):
 * Distrito | Indicador | Unidad | Anio | Valor | Fuente
 */
class DataRecordsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use Importable;

    /** @var array<int, string> */
    public array $errors = [];

    public int $imported = 0;

    public int $updated = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;

            try {
                $this->importRow($row, $line);
            } catch (\Throwable $e) {
                $this->errors[] = "Fila {$line}: {$e->getMessage()}";
            }
        }
    }

    private function importRow(Collection $row, int $line): void
    {
        $districtName = trim((string) ($row['distrito'] ?? ''));
        $indicatorName = trim((string) ($row['indicador'] ?? ''));
        $year = (int) ($row['anio'] ?? $row['ano'] ?? 0);
        $value = $row['valor'] ?? null;
        $source = trim((string) ($row['fuente'] ?? ''));
        $unit = trim((string) ($row['unidad'] ?? ''));

        if ($districtName === '' || $indicatorName === '') {
            throw new \InvalidArgumentException('Distrito e Indicador son obligatorios.');
        }

        if ($year < DataRecord::MIN_YEAR || $year > DataRecord::MAX_YEAR) {
            throw new \InvalidArgumentException('Año fuera del periodo permitido ('.DataRecord::MIN_YEAR.'-'.DataRecord::MAX_YEAR.').');
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('Valor debe ser numerico.');
        }

        $sourceKey = strtoupper($source);

        if (! array_key_exists($sourceKey, DataRecord::SOURCES)) {
            throw new \InvalidArgumentException('Fuente invalida: "'.$source.'". Use UGEL, ESCALE, INEI o MIDIS.');
        }

        $district = District::firstOrCreate(['name' => $districtName]);

        $indicator = Indicator::firstOrCreate(
            ['name' => $indicatorName],
            ['unit' => $unit !== '' ? $unit : null],
        );

        $exists = DataRecord::query()
            ->where('district_id', $district->id)
            ->where('indicator_id', $indicator->id)
            ->where('year', $year)
            ->exists();

        DataRecord::query()->updateOrCreate(
            ['district_id' => $district->id, 'indicator_id' => $indicator->id, 'year' => $year],
            ['value' => $value, 'source' => $sourceKey],
        );

        $exists ? $this->updated++ : $this->imported++;
    }
}
