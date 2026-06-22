<?php

namespace App\Filament\Resources\EducationalInstitutionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Niveles/modalidades censados de una institucion educativa (Inicial,
 * Primaria, Secundaria, etc.), con sus alumnos, docentes y secciones por año.
 */
class LevelCensusesRelationManager extends RelationManager
{
    protected static string $relationship = 'levelCensuses';

    protected static ?string $title = 'Censo por nivel';

    protected static ?string $modelLabel = 'registro de nivel';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('modular_code')
                    ->label('Código modular')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('level')
                    ->label('Nivel / Modalidad')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('program_type')
                    ->label('Tipo de programa')
                    ->maxLength(255),
                Forms\Components\TextInput::make('students')
                    ->label('Alumnos')
                    ->numeric(),
                Forms\Components\TextInput::make('teachers')
                    ->label('Docentes')
                    ->numeric(),
                Forms\Components\TextInput::make('sections')
                    ->label('Secciones')
                    ->numeric(),
                Forms\Components\TextInput::make('census_year')
                    ->label('Año de censo')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('level')
            ->columns([
                Tables\Columns\TextColumn::make('level')
                    ->label('Nivel / Modalidad')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('program_type')
                    ->label('Tipo de programa')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('students')
                    ->label('Alumnos')
                    ->numeric()
                    ->sortable()
                    ->placeholder('S/D'),
                Tables\Columns\TextColumn::make('teachers')
                    ->label('Docentes')
                    ->numeric()
                    ->sortable()
                    ->placeholder('S/D'),
                Tables\Columns\TextColumn::make('sections')
                    ->label('Secciones')
                    ->numeric()
                    ->sortable()
                    ->placeholder('S/D'),
                Tables\Columns\TextColumn::make('census_year')
                    ->label('Año')
                    ->sortable(),
            ])
            ->defaultSort('census_year', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
