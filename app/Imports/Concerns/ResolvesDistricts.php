<?php

namespace App\Imports\Concerns;

use App\Models\District;
use Illuminate\Support\Str;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Resuelve el nombre de distrito de una fila de archivo (UGEL/ESCALE) contra
 * los District existentes, tolerando tildes y pequeñas variaciones de escritura.
 */
trait ResolvesDistricts
{
    /** Cache de District resueltos: [nombre_normalizado => District] */
    private array $districtCache = [];

    /**
     * Pre-carga todos los distritos en memoria.
     */
    private function warmDistrictCache(): void
    {
        District::query()->each(function (District $district): void {
            $key = $this->normalizeDistrictName($district->name);
            $this->districtCache[$key] = $district;
        });
    }

    /**
     * Normaliza el nombre del distrito: mayusculas consistentes,
     * sin espacios extra, tildes normalizadas.
     */
    private function normalizeDistrictName(string $name): string
    {
        $name = trim($name);
        $name = Str::of($name)->upper()->trim()->toString();

        return preg_replace('/\s+/', ' ', $name);
    }

    /**
     * Resuelve un District a partir del nombre de la fila.
     * Primero busca por nombre exacto (normalizado), luego
     * intenta coincidencia parcial si hay exactamente un candidato.
     *
     * @throws \RuntimeException si no se puede resolver.
     */
    private function resolveDistrict(string $rawName): District
    {
        $normalized = $this->normalizeDistrictName($rawName);

        if (isset($this->districtCache[$normalized])) {
            return $this->districtCache[$normalized];
        }

        // Busqueda aproximada: quitar tildes y usar levenshtein con tolerancia 2
        $candidates = [];
        foreach ($this->districtCache as $district) {
            $distName = $this->normalizeDistrictName($district->name);

            $cleanNormalized = Str::ascii($normalized);
            $cleanCached = Str::ascii($distName);

            if ($cleanNormalized === $cleanCached) {
                $candidates[] = $district;
            } elseif (levenshtein($cleanNormalized, $cleanCached) <= 2) {
                $candidates[] = $district;
            }
        }

        if (count($candidates) === 1) {
            $this->districtCache[$normalized] = $candidates[0];

            return $candidates[0];
        }

        throw new \RuntimeException(
            "No se pudo resolver el distrito '{$rawName}'. ".
            (count($candidates) > 1
                ? 'Coincide con multiples distritos: '.implode(', ', array_map(fn ($d) => $d->name, $candidates))
                : 'No coincide con ningun distrito registrado.'
            )
        );
    }
}
