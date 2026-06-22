<?php

namespace App\Imports;

use App\Imports\Concerns\ResolvesDistricts;
use App\Models\District;
use App\Models\EducationalInstitution;
use App\Models\InstitutionLevelCensus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Importador del padron de Instituciones Educativas de ESCALE (export
 * "Instituciones"), con una fila por cada IE x Nivel/Modalidad y sus datos
 * de censo: Alumnos, Docentes y Secciones.
 *
 * Comportamiento:
 *   1. Detecta la fila de encabezado real buscando la celda "Distrito".
 *   2. Ubica las columnas por nombre (tolerante a tildes/mayusculas).
 *   3. Solo procesa filas de la provincia Quispicanchi (si la columna existe).
 *   4. Por cada fila: resuelve/crea la EducationalInstitution (agrupada por
 *      "Codigo de local" cuando esta presente, o por nombre+distrito+centro
 *      poblado como respaldo), y crea/actualiza su InstitutionLevelCensus
 *      para el nivel y el año de censo indicado (upsert por codigo modular + año).
 *   5. Celdas vacias de Alumnos/Docentes/Secciones quedan como NULL (IE sin
 *      reportar ese dato), no como 0, para no distorsionar los promedios.
 */
class EscaleInstitutionsImport implements SkipsEmptyRows, ToCollection
{
    use Importable;
    use ResolvesDistricts;

    /** @var array<int, string> */
    public array $errors = [];

    public int $institutionsCreated = 0;

    public int $censusCreated = 0;

    public int $censusUpdated = 0;

    public int $skipped = 0;

    /** Cache de EducationalInstitution resueltas por clave de agrupacion. */
    private array $institutionCache = [];

    public function __construct(
        private readonly int $censusYear,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->warmDistrictCache();

        $headerIndex = $this->findHeaderRowIndex($rows);

        if ($headerIndex === null) {
            $this->errors[] = "No se encontro una fila de cabecera con la columna 'Distrito' en el archivo.";

            return;
        }

        $columns = $this->mapColumns($rows->get($headerIndex));

        if ($columns['distrito'] === null || $columns['nombre'] === null || $columns['nivel'] === null) {
            $this->errors[] = "El archivo debe traer al menos las columnas 'Distrito', 'Nombre de IE' y 'Nivel / Modalidad'.";

            return;
        }

        foreach ($rows as $index => $row) {
            if ($index <= $headerIndex) {
                continue;
            }

            $line = $index + 1;

            try {
                $this->processRow($row, $columns);
            } catch (\Throwable $e) {
                $this->errors[] = "Fila {$line}: {$e->getMessage()}";
            }
        }
    }

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
     * @return array<string, ?int>
     */
    private function mapColumns(Collection $headerRow): array
    {
        $columns = [
            'modular' => null,
            'local' => null,
            'institucion' => null,
            'nombre' => null,
            'nivel' => null,
            'gestion' => null,
            'dependencia' => null,
            'centro_poblado' => null,
            'provincia' => null,
            'distrito' => null,
            'programa' => null,
            'alumnos' => null,
            'docentes' => null,
            'secciones' => null,
        ];

        foreach ($headerRow as $idx => $value) {
            $lower = Str::lower(Str::ascii(trim((string) $value)));

            if ($lower === '') {
                continue;
            }

            match (true) {
                str_contains($lower, 'codigo modular') => $columns['modular'] = $idx,
                str_contains($lower, 'codigo de local') => $columns['local'] = $idx,
                str_contains($lower, 'codigo de institucion') => $columns['institucion'] = $idx,
                str_contains($lower, 'nombre de ie') => $columns['nombre'] = $idx,
                str_contains($lower, 'nivel') => $columns['nivel'] = $idx,
                str_contains($lower, 'tipo de gestion') => $columns['gestion'] = $idx,
                $lower === 'dependencia' => $columns['dependencia'] = $idx,
                str_contains($lower, 'centro poblado') => $columns['centro_poblado'] = $idx,
                $lower === 'provincia' => $columns['provincia'] = $idx,
                $lower === 'distrito' => $columns['distrito'] = $idx,
                str_contains($lower, 'tipo de programa') => $columns['programa'] = $idx,
                str_contains($lower, 'alumnos') => $columns['alumnos'] = $idx,
                str_contains($lower, 'docentes') => $columns['docentes'] = $idx,
                str_contains($lower, 'secciones') => $columns['secciones'] = $idx,
                default => null,
            };
        }

        return $columns;
    }

    /**
     * @param  array<string, ?int>  $columns
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    private function processRow(Collection $row, array $columns): void
    {
        $provincia = $columns['provincia'] !== null ? trim((string) ($row[$columns['provincia']] ?? '')) : null;

        if ($provincia !== null && $provincia !== '' && Str::ascii(Str::lower($provincia)) !== 'quispicanchi') {
            $this->skipped++;

            return;
        }

        $districtName = trim((string) ($row[$columns['distrito']] ?? ''));
        $name = trim((string) ($row[$columns['nombre']] ?? ''));
        $level = trim((string) ($row[$columns['nivel']] ?? ''));

        if ($districtName === '' || $name === '' || $level === '') {
            throw new \InvalidArgumentException('Fila sin distrito, nombre de IE o nivel/modalidad.');
        }

        $modularCode = $columns['modular'] !== null ? trim((string) ($row[$columns['modular']] ?? '')) : '';

        if ($modularCode === '') {
            throw new \InvalidArgumentException("No trae 'Codigo modular' para la IE '{$name}'.");
        }

        $district = $this->resolveDistrict($districtName);

        $localCode = $columns['local'] !== null ? trim((string) ($row[$columns['local']] ?? '')) : '';
        $populatedCenter = $columns['centro_poblado'] !== null ? trim((string) ($row[$columns['centro_poblado']] ?? '')) : '';
        $managementType = $columns['gestion'] !== null ? trim((string) ($row[$columns['gestion']] ?? '')) : '';
        $dependency = $columns['dependencia'] !== null ? trim((string) ($row[$columns['dependencia']] ?? '')) : '';
        $institutionCode = $columns['institucion'] !== null ? trim((string) ($row[$columns['institucion']] ?? '')) : '';
        $programType = $columns['programa'] !== null ? trim((string) ($row[$columns['programa']] ?? '')) : '';

        $institution = $this->resolveInstitution(
            district: $district,
            localCode: $localCode,
            name: $name,
            managementType: $managementType,
            dependency: $dependency,
            populatedCenter: $populatedCenter,
        );

        $exists = InstitutionLevelCensus::query()
            ->where('modular_code', $modularCode)
            ->where('census_year', $this->censusYear)
            ->exists();

        InstitutionLevelCensus::query()->updateOrCreate(
            [
                'modular_code' => $modularCode,
                'census_year' => $this->censusYear,
            ],
            [
                'educational_institution_id' => $institution->id,
                'institution_code' => $institutionCode !== '' ? $institutionCode : null,
                'level' => $level,
                'program_type' => $programType !== '' ? $programType : null,
                'students' => $this->parseNullableInt($columns['alumnos'] !== null ? ($row[$columns['alumnos']] ?? null) : null),
                'teachers' => $this->parseNullableInt($columns['docentes'] !== null ? ($row[$columns['docentes']] ?? null) : null),
                'sections' => $this->parseNullableInt($columns['secciones'] !== null ? ($row[$columns['secciones']] ?? null) : null),
            ],
        );

        $exists ? $this->censusUpdated++ : $this->censusCreated++;
    }

    private function parseNullableInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Resuelve (o crea) la EducationalInstitution. Se agrupa por "Codigo de
     * local" cuando esta presente; si no, por nombre + distrito + centro
     * poblado, para no duplicar la misma IE en cada nivel que ofrece.
     */
    private function resolveInstitution(
        District $district,
        string $localCode,
        string $name,
        string $managementType,
        string $dependency,
        string $populatedCenter,
    ): EducationalInstitution {
        $cacheKey = $localCode !== ''
            ? 'local:'.$localCode
            : 'fallback:'.Str::upper($name).'|'.$district->id.'|'.Str::upper($populatedCenter);

        if (isset($this->institutionCache[$cacheKey])) {
            return $this->institutionCache[$cacheKey];
        }

        $query = EducationalInstitution::query();

        if ($localCode !== '') {
            $institution = $query->where('local_code', $localCode)->first();
        } else {
            $institution = $query
                ->where('name', $name)
                ->where('district_id', $district->id)
                ->where('populated_center', $populatedCenter !== '' ? $populatedCenter : null)
                ->whereNull('local_code')
                ->first();
        }

        if ($institution === null) {
            $institution = EducationalInstitution::query()->create([
                'district_id' => $district->id,
                'local_code' => $localCode !== '' ? $localCode : null,
                'name' => $name,
                'management_type' => $managementType !== '' ? $managementType : 'Sin especificar',
                'dependency' => $dependency !== '' ? $dependency : null,
                'populated_center' => $populatedCenter !== '' ? $populatedCenter : null,
            ]);

            $this->institutionsCreated++;
        }

        $this->institutionCache[$cacheKey] = $institution;

        return $institution;
    }
}
