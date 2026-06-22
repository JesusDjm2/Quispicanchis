<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Institucion educativa (IE) segun el padron ESCALE, identificada por su
 * "Codigo de local" (cuando lo trae el censo). Una IE puede ofrecer varios
 * niveles/modalidades (Inicial, Primaria, Secundaria, etc.), cada uno
 * registrado en InstitutionLevelCensus.
 */
class EducationalInstitution extends Model
{
    /** Tipos de gestion segun el padron ESCALE. */
    public const MANAGEMENT_TYPES = [
        'Pública de gestión directa' => 'Pública de gestión directa',
        'Pública de gestión privada' => 'Pública de gestión privada',
        'Privada' => 'Privada',
    ];

    protected $fillable = [
        'district_id',
        'local_code',
        'name',
        'management_type',
        'dependency',
        'populated_center',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function levelCensuses(): HasMany
    {
        return $this->hasMany(InstitutionLevelCensus::class);
    }
}
