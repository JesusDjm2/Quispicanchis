<?php

namespace App\Filament\Resources\DataRecordResource\Pages;

use App\Exports\DataRecordsExport;
use App\Filament\Resources\DataRecordResource;
use App\Imports\DataRecordsImport;
use App\Imports\MatriculaConsolidadaImport;
use App\Models\DataRecord;
use App\Models\Indicator;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class ListDataRecords extends ListRecords
{
    protected static string $resource = DataRecordResource::class;

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),
            'recientes' => Tab::make('Recientes (7 días)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                ->badge(DataRecord::query()->where('created_at', '>=', now()->subDays(7))->count()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Importar Excel (UGEL)')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $import = new DataRecordsImport;

                    Excel::import($import, $data['file'], 'local');

                    Notification::make()
                        ->title('Importacion completada')
                        ->body(sprintf(
                            '%d registros creados, %d actualizados%s',
                            $import->imported,
                            $import->updated,
                            $import->errors === [] ? '.' : ', '.count($import->errors).' filas con errores.',
                        ))
                        ->when($import->errors !== [], fn (Notification $notification) => $notification
                            ->danger()
                            ->body(implode("\n", array_slice($import->errors, 0, 10))))
                        ->when($import->errors === [], fn (Notification $notification) => $notification->success())
                        ->persistent()
                        ->send();
                }),
            Actions\Action::make('importMatricula')
                ->label('Importar Matrícula Consolidada (UGEL)')
                ->icon('heroicon-o-academic-cap')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('indicator_id')
                        ->label('Indicador base')
                        ->options(Indicator::query()->orderBy('name')->pluck('name', 'id'))
                        ->default(1) // 'Matrícula Escolar'
                        ->searchable()
                        ->required()
                        ->helperText('Indicador al que se asignaran los valores importados.'),
                    Forms\Components\Select::make('year')
                        ->label('Año (respaldo, opcional)')
                        ->options(array_combine(DataRecord::availableYears(), DataRecord::availableYears()))
                        ->placeholder('El archivo trae el año por fila')
                        ->helperText('Dejalo vacio si el archivo ya trae una columna "Año"/"Anio"/"Periodo" por fila (cada fila usara su propio valor). Solo elige un año aqui si el archivo NO trae esa columna y todas las filas son de un mismo año.'),
                    Forms\Components\FileUpload::make('file')
                        ->label('Archivo Excel (consolidado UGEL)')
                        ->disk('local')
                        ->directory('imports/matricula-consolidada')
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $import = new MatriculaConsolidadaImport(
                        indicatorId: (int) $data['indicator_id'],
                        year: filled($data['year'] ?? null) ? (int) $data['year'] : null,
                    );

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

                    $indicatorName = Indicator::find($data['indicator_id'])?->name ?? 'ID '.$data['indicator_id'];

                    Notification::make()
                        ->title('Importacion de matricula completada')
                        ->body(sprintf(
                            'Indicador: %s%s | %d registros procesados (%d creados, %d actualizados)%s',
                            $indicatorName,
                            filled($data['year'] ?? null) ? ' | Año respaldo: '.$data['year'] : '',
                            $import->imported + $import->updated,
                            $import->imported,
                            $import->updated,
                            $import->errors === [] ? '.' : ', '.count($import->errors).' errores.',
                        ))
                        ->when($import->errors !== [], fn (Notification $notification) => $notification
                            ->danger()
                            ->body(implode("\n", array_slice($import->errors, 0, 10))))
                        ->when($import->errors === [], fn (Notification $notification) => $notification->success())
                        ->persistent()
                        ->send();
                }),
            Actions\Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn (): BinaryFileResponse => (new DataRecordsExport)->download(
                    'pel-quispicanchi-'.now()->format('Y-m-d').'.xlsx',
                )),
            Actions\Action::make('exportWord')
                ->label('Exportar Word')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('indicator_id')
                        ->label('Indicador a incluir en el reporte')
                        ->options(Indicator::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data, Actions\Action $action): StreamedResponse {
                    try {
                        return app(ExportService::class)->downloadWord(
                            indicatorId: (int) $data['indicator_id'],
                            filename: 'pel-quispicanchi-'.Str::slug($data['indicator_id'].'-'.now()->format('Y-m-d')).'.docx',
                        );
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('No se pudo generar el reporte')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}
