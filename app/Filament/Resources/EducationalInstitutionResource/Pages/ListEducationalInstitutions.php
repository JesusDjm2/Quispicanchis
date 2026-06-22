<?php

namespace App\Filament\Resources\EducationalInstitutionResource\Pages;

use App\Filament\Resources\EducationalInstitutionResource;
use App\Imports\EscaleInstitutionsImport;
use App\Models\DataRecord;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class ListEducationalInstitutions extends ListRecords
{
    protected static string $resource = EducationalInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importEscale')
                ->label('Importar censo ESCALE')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('census_year')
                        ->label('Año del censo')
                        ->options(array_combine(
                            DataRecord::availableYears(),
                            DataRecord::availableYears(),
                        ))
                        ->default(now()->year)
                        ->required()
                        ->helperText('Año del censo educativo ESCALE (no viene en el archivo, se indica aquí).'),
                    Forms\Components\FileUpload::make('file')
                        ->label('Archivo ESCALE "Instituciones" (Excel/CSV)')
                        ->disk('local')
                        ->directory('imports/escale-instituciones')
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $import = new EscaleInstitutionsImport(censusYear: (int) $data['census_year']);

                    try {
                        Excel::import($import, $data['file'], 'local');
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al importar')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Importación del censo ESCALE completada')
                        ->body(sprintf(
                            '%d instituciones nuevas | %d registros de nivel creados, %d actualizados%s',
                            $import->institutionsCreated,
                            $import->censusCreated,
                            $import->censusUpdated,
                            $import->errors === [] ? '.' : ', '.count($import->errors).' filas con errores.',
                        ))
                        ->when($import->errors !== [], fn (Notification $notification) => $notification
                            ->danger()
                            ->body(implode("\n", array_slice($import->errors, 0, 10))))
                        ->when($import->errors === [], fn (Notification $notification) => $notification->success())
                        ->persistent()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
