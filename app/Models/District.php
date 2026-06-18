<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class District extends Model
{
    protected $fillable = [
        'name',
        'ubigeo',
    ];

    public function dataRecords(): HasMany
    {
        return $this->hasMany(DataRecord::class);
    }
}
