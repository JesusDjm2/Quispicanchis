<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataRecordResource\Pages;
use App\Models\DataRecord;
use App\Models\District;
use App\Models\Indicator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Gestion de los valores de cada Indicator por District y Year (2022-2026),
 * con registro obligatorio de la Fuente (UGEL, ESCALE, INEI, MIDIS).
 */
class DataRecordResource extends Resource
{
    protected static ?string $model = DataRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationGroup = 'PEL Quispicanchi';

    protected static ?string $navigationLabel = 'Datos por distrito';

    protected static ?string $modelLabel = 'Registro de datos';

    protected static ?string $pluralModelLabel = 'Registros de datos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('district_id')
                    ->label('Distrito')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('indicator_id')
                    ->label('Indicador')
                    ->relationship('indicator', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('year')
                    ->label('Año')
                    ->options(array_combine(DataRecord::availableYears(), DataRecord::availableYears()))
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule, Forms\Get $get) => $rule
                            ->where('district_id', $get('district_id'))
                            ->where('indicator_id', $get('indicator_id')),
                    )
                    ->validationMessages([
                        'unique' => 'Ya existe un registro para este distrito, indicador y año.',
                    ]),
                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('source')
                    ->label('Fuente')
                    ->options(DataRecord::SOURCES)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('district.name')
                    ->label('Distrito')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('indicator.name')
                    ->label('Indicador')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Fuente')
                    ->badge(),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                SelectFilter::make('district_id')
                    ->label('Distrito')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('indicator_id')
                    ->label('Indicador')
                    ->relationship('indicator', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('year')
                    ->label('Año')
                    ->options(array_combine(DataRecord::availableYears(), DataRecord::availableYears())),
                SelectFilter::make('source')
                    ->label('Fuente')
                    ->options(DataRecord::SOURCES),
            ])
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataRecords::route('/'),
            'create' => Pages\CreateDataRecord::route('/create'),
            'edit' => Pages\EditDataRecord::route('/{record}/edit'),
        ];
    }
}
