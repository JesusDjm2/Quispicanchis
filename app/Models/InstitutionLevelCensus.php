<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Censo ESCALE de alumnos, docentes y secciones de una EducationalInstitution
 * para un Nivel/Modalidad y año de censo especifico. Una misma IE tiene un
 * registro por cada nivel que ofrece (ej. Inicial - Jardín, Primaria, Secundaria).
 */
class InstitutionLevelCensus extends Model
{
    protected $table = 'institution_level_census';

    protected $fillable = [
        'educational_institution_id',
        'modular_code',
        'institution_code',
        'level',
        'program_type',
        'students',
        'teachers',
        'sections',
        'census_year',
    ];

    protected $casts = [
        'students' => 'integer',
        'teachers' => 'integer',
        'sections' => 'integer',
        'census_year' => 'integer',
    ];

    public function educationalInstitution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class);
    }

    /**
     * Grupo de nivel "amplio" para graficos (ej. "Inicial - Jardín" → "Inicial").
     */
    public function levelGroup(): string
    {
        return trim(explode(' - ', $this->level)[0]);
    }
}
