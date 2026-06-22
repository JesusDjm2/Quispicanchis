<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EducationalInstitutionResource\Pages;
use App\Filament\Resources\EducationalInstitutionResource\RelationManagers\LevelCensusesRelationManager;
use App\Models\EducationalInstitution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Padron de Instituciones Educativas (ESCALE) de la provincia de Quispicanchi,
 * con su censo de alumnos, docentes y secciones por nivel/modalidad y año.
 */
class EducationalInstitutionResource extends Resource
{
    protected static ?string $model = EducationalInstitution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'PEL Quispicanchi';

    protected static ?string $navigationLabel = 'Instituciones educativas';

    protected static ?string $modelLabel = 'Institución educativa';

    protected static ?string $pluralModelLabel = 'Instituciones educativas';

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
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de IE')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('local_code')
                    ->label('Código de local')
                    ->maxLength(255),
                Forms\Components\Select::make('management_type')
                    ->label('Tipo de gestión')
                    ->options(EducationalInstitution::MANAGEMENT_TYPES)
                    ->required(),
                Forms\Components\TextInput::make('dependency')
                    ->label('Dependencia')
                    ->maxLength(255),
                Forms\Components\TextInput::make('populated_center')
                    ->label('Centro poblado')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de IE')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('district.name')
                    ->label('Distrito')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('management_type')
                    ->label('Gestión')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('populated_center')
                    ->label('Centro poblado')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('local_code')
                    ->label('Código de local')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('level_censuses_count')
                    ->label('Niveles censados')
                    ->counts('levelCensuses')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('district_id')
                    ->label('Distrito')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('management_type')
                    ->label('Gestión')
                    ->options(EducationalInstitution::MANAGEMENT_TYPES),
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
            LevelCensusesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEducationalInstitutions::route('/'),
            'create' => Pages\CreateEducationalInstitution::route('/create'),
            'edit' => Pages\EditEducationalInstitution::route('/{record}/edit'),
        ];
    }
}
