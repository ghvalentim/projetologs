<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentsResource\Pages;
use App\Models\Department;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // Classe pai unificada do Filament v3
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class DepartmentResource extends Resource
{

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $modelLabel = 'Departamento';
    protected static ?string $pluralModelLabel = 'Departamentos';
    protected static ?string $navigationLabel = 'Departamentos';

    // Alterado de Form $form para Schema $form para bater com a classe pai
    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                TextInput::make('name')
                    ->label('Nome do Departamento / Ponto de Atendimento')
                    ->required()
                    ->placeholder('Ex: Agrupamento Escolar de Oliveira do Hospital')
                    ->columnSpanFull(),

                Select::make('type')
                    ->label('Tipo de Setor')
                    ->options([
                        'Educação' => 'Educação / Escolas',
                        'Saúde' => 'Saúde / Unidades Médicas',
                        'Administrativo' => 'Administrativo / Serviços Centrais',
                        'Gabinete' => 'Gabinete / Vereação',
                    ])
                    ->required(),

                TextInput::make('responsible_person')
                    ->label('Responsável / Técnico Local')
                    ->placeholder('Nome do contacto principal'),

                TextInput::make('contact_email')
                    ->label('E-mail para Alertas')
                    ->email()
                    ->placeholder('Ex: tic.escola@municipio.pt')
                    ->columnSpanFull(),
            ]);
    }

    // Ajustado para usar a herança correta de tabelas do v3
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Educação' => 'info',
                        'Saúde' => 'success',
                        'Administrativo' => 'gray',
                        'Gabinete' => 'warning',
                        default => 'secondary',
                    }),

                TextColumn::make('responsible_person')
                    ->label('Responsável')
                    ->searchable(),

                TextColumn::make('licenses_count')
                    ->label('Licenças Ativas')
                    ->counts('licenses')
                    ->badge(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }

}