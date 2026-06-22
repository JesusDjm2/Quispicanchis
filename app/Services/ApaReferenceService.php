<?php

namespace App\Services;

use App\Models\DataRecord;
use App\Models\Indicator;
use Illuminate\Support\Collection;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Resuelve, para un indicador y un rango de años, las referencias bibliográficas
 * en formato APA 7.a edicion correspondientes a las fuentes (UGEL/ESCALE/INEI/MIDIS)
 * que realmente se usaron para cargar sus DataRecord, leyendo el catalogo fijo
 * de config('references').
 */
class ApaReferenceService
{
    /**
     * Cadena APA 7 de una clave de fuente puntual (ver DataRecord::SOURCES).
     * Las claves compuestas (ej. "UGEL QUISPICANCHI / ESCALE") se expanden
     * a las referencias de sus fuentes base, sin duplicar.
     *
     * @return array<int, string>
     */
    public function resolve(string $sourceKey): array
    {
        // El catalogo se indexa en mayusculas; algunos importadores guardan
        // el valor "humano" (ej. "UGEL Quispicanchi / ESCALE") en vez de la
        // clave exacta de DataRecord::SOURCES, asi que normalizamos antes de buscar.
        $entry = config('references.'.strtoupper(trim($sourceKey)));

        if ($entry === null) {
            return [];
        }

        if (is_array($entry)) {
            return collect($entry)
                ->flatMap(fn (string $baseKey) => $this->resolve($baseKey))
                ->unique()
                ->values()
                ->all();
        }

        return [$entry];
    }

    /**
     * Referencias APA 7 (orden alfabetico, sin duplicados) de todas las fuentes
     * realmente usadas por los DataRecord de un indicador en el rango de años dado.
     *
     * @param  array<int, int>|null  $years
     * @return array<int, string>
     */
    public function forIndicator(Indicator $indicator, ?array $years = null): array
    {
        $sources = DataRecord::query()
            ->where('indicator_id', $indicator->id)
            ->when($years !== null, fn ($query) => $query->whereIn('year', $years))
            ->distinct()
            ->pluck('source');

        return $sources
            ->flatMap(fn (string $sourceKey) => $this->resolve($sourceKey))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Construye la linea "Fuente: ..." a partir de las fuentes (DataRecord::SOURCES)
     * realmente usadas en un conjunto de registros, en vez de un texto fijo.
     * Si el conjunto esta vacio, recae en el rotulo generico del contrato.
     *
     * @param  Collection<int, DataRecord>  $records
     */
    public function sourceLine(Collection $records): string
    {
        $labels = $records
            ->pluck('source')
            ->map(fn (string $sourceKey) => DataRecord::SOURCES[strtoupper(trim($sourceKey))] ?? $sourceKey)
            ->unique()
            ->sort()
            ->values();

        if ($labels->isEmpty()) {
            return 'Fuente: UGEL Quispicanchi / ESCALE';
        }

        return 'Fuente: '.$labels->implode(' / ');
    }
}
