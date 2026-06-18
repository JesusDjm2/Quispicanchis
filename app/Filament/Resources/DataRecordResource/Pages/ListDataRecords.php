<?php

namespace App\Filament\Resources\DataRecordResource\Pages;

use App\Exports\DataRecordsExport;
use App\Filament\Resources\DataRecordResource;
use App\Imports\DataRecordsImport;
use App\Models\Indicator;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class ListDataRecords extends ListRecords
{
    protected static string $resource = DataRecordResource::class;

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
            Actions\Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn (): StreamedResponse => (new DataRecordsExport)->download(
                    'pel-quispicanchi-'.now()->format('Y-m-d').'.xlsx',
                )),
            Actions\Action::make('exportWord')
                ->label('Exportar Word')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->form([
                    Forms\Components\CheckboxList::make('indicator_ids')
                        ->label('Indicadores a incluir en el reporte')
                        ->options(Indicator::query()->orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->columns(2),
                ])
                ->action(function (array $data, Actions\Action $action): StreamedResponse {
                    $indicators = Indicator::query()->whereIn('id', $data['indicator_ids'])->get();

                    try {
                        $phpWord = app(ExportService::class)->generateWord($indicators);
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('No se pudo generar el reporte')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }

                    return response()->streamDownload(function () use ($phpWord) {
                        IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
                    }, 'pel-quispicanchi-'.Str::slug(now()->format('Y-m-d')).'.docx');
                }),
            Actions\CreateAction::make(),
        ];
    }
}
