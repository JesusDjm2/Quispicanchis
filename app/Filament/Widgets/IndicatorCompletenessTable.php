<?php

namespace App\Filament\Widgets;

use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Muestra, por indicador, cuantos de los registros esperados (distritos x años)
 * realmente existen. El export a Word ya no bloquea por datos incompletos
 * (las celdas faltantes se muestran como "S/D"), pero esta tabla ayuda a ver
 * de antemano que indicadores aun tienen huecos de informacion.
 */
class IndicatorCompletenessTable extends BaseWidget
{
    protected static ?string $heading = 'Completitud de datos por indicador';

    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.table-widget';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $expected = District::count() * count(DataRecord::availableYears());

        return $table
            ->query(Indicator::query()->withCount('dataRecords'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Indicador'),
                Tables\Columns\TextColumn::make('data_records_count')
                    ->label('Registros')
                    ->formatStateUsing(fn (int $state): string => "{$state} / {$expected}"),
                Tables\Columns\TextColumn::make('completeness')
                    ->label('Completitud')
                    ->state(fn (Indicator $record): string => $expected > 0
                        ? round($record->data_records_count / $expected * 100).'%'
                        : '0%')
                    ->badge()
                    ->color(fn (Indicator $record): string => match (true) {
                        $record->data_records_count >= $expected => 'success',
                        $record->data_records_count >= $expected / 2 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('data_records_count', 'desc')
            ->paginated(false);
    }

    protected function getSourceLine(): string
    {
        return 'Fuente: UGEL Quispicanchi / ESCALE / INEI / MIDIS';
    }
}
