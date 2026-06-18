<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IndicatorResource\Pages;
use App\Models\Indicator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class IndicatorResource extends Resource
{
    protected static ?string $model = Indicator::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'PEL Quispicanchi';

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $modelLabel = 'Indicador';

    protected static ?string $pluralModelLabel = 'Indicadores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del indicador')
                    ->helperText('Ej. Matricula, Logros de aprendizaje, Desercion escolar')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('unit')
                    ->label('Unidad de medida')
                    ->helperText('Ej. estudiantes, %, docentes')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descripcion')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Indicador')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unidad'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripcion')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('data_records_count')
                    ->label('Registros de datos')
                    ->counts('dataRecords')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIndicators::route('/'),
        ];
    }
}
