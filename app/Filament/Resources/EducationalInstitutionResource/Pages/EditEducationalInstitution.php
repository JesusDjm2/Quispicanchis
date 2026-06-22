<?php

namespace App\Filament\Resources\EducationalInstitutionResource\Pages;

use App\Filament\Resources\EducationalInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class EditEducationalInstitution extends EditRecord
{
    protected static string $resource = EducationalInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
