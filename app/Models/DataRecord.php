<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Registro de valor de un Indicator para un District en un Year especifico,
 * segun lo exigido por el contrato: progresion historica 2022-2026.
 */
class DataRecord extends Model
{
    /** Periodo exigido por el contrato "PEL Quispicanchi al 2036". */
    public const MIN_YEAR = 2022;

    public const MAX_YEAR = 2026;

    /** Fuentes de informacion admitidas segun el contrato. */
    public const SOURCES = [
        'UGEL' => 'UGEL',
        'ESCALE' => 'ESCALE',
        'INEI' => 'INEI',
        'MIDIS' => 'MIDIS',
        'UGEL QUISPICANCHI / ESCALE' => 'UGEL Quispicanchi / ESCALE',
    ];

    protected $fillable = [
        'district_id',
        'indicator_id',
        'year',
        'value',
        'source',
    ];

    protected $casts = [
        'year' => 'integer',
        'value' => 'decimal:2',
    ];

    public static function availableYears(): array
    {
        return range(self::MIN_YEAR, self::MAX_YEAR);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
