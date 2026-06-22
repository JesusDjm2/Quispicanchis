<?php

namespace App\Filament\Resources\DataRecordResource\Pages;

use App\Filament\Resources\DataRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class EditDataRecord extends EditRecord
{
    protected static string $resource = DataRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
