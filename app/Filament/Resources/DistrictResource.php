<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistrictResource\Pages;
use App\Models\District;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 */
class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'PEL Quispicanchi';

    protected static ?string $navigationLabel = 'Distritos';

    protected static ?string $modelLabel = 'Distrito';

    protected static ?string $pluralModelLabel = 'Distritos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del distrito')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ubigeo')
                    ->label('Ubigeo (INEI)')
                    ->maxLength(6)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Distrito')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ubigeo')
                    ->label('Ubigeo')
                    ->sortable(),
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
            'index' => Pages\ManageDistricts::route('/'),
        ];
    }
}
