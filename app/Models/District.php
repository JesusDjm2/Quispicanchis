<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Las 12 entidades politicas de la provincia de Quispicanchi. `managed_by_ugel`
 * distingue los 10 distritos que gestiona directamente la UGEL Quispicanchi
 * (matricula, docentes, IIEE, etc.) de Lucre y Oropesa, que solo aportan datos
 * INEI/MIDIS/ESCALE al consolidado provincial.
 */
class District extends Model
{
    protected $fillable = [
        'name',
        'ubigeo',
        'managed_by_ugel',
    ];

    protected $casts = [
        'managed_by_ugel' => 'boolean',
    ];

    /** Los 10 distritos atendidos directamente por la UGEL Quispicanchi. */
    public function scopeUgelManaged(Builder $query): Builder
    {
        return $query->where('managed_by_ugel', true);
    }

    public function dataRecords(): HasMany
    {
        return $this->hasMany(DataRecord::class);
    }

    public function educationalInstitutions(): HasMany
    {
        return $this->hasMany(EducationalInstitution::class);
    }
}
