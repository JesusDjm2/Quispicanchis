<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class Indicator extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'description',
    ];

    public function dataRecords(): HasMany
    {
        return $this->hasMany(DataRecord::class);
    }
}
