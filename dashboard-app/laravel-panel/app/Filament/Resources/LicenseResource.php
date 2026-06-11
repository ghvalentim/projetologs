<?php

namespace App\Filament\Resources;

use App\Models\License;
use App\Filament\Resources\LicensesResource\Pages;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // Classe pai unificada do Filament v3
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Carbon\Carbon;

class LicenseResource extends Resource
{

    protected static ?string $model = License::class;
    protected static ?string $modelLabel = 'Licença';
    protected static ?string $pluralModelLabel = 'Licenças';
    protected static ?string $navigationLabel = 'Licenças';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';

    public static function form(Schema $form): Schema
{
    return $form
        ->components([
            Select::make('department_id')
                ->relationship('department', 'name')
                ->label('Ponto de Atendimento / Departamento')
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),

            TextInput::make('software_name')
                ->label('Nome do Software')
                ->required()
                ->columnSpanFull(), // Ocupa a largura total

            TextInput::make('license_key')
                ->label('Chave de Ativação / Serial'),

            TextInput::make('supplier')
                ->label('Fornecedor / Contrato'),

            // Em vez de usar a classe Grid, dividimos o comportamento direto nos campos usando columnSpan:
            TextInput::make('total_slots')
                ->label('Total de Slots Comprados')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required(),

            TextInput::make('used_slots')
                ->label('Slots Instalados/Usados')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->required()
                ->rules([
        // Regra nativa do Laravel: "Menor ou igual ao campo total_slots"
        'lte:total_slots' 
    ])
    ->validationMessages([
        // Customiza a mensagem para o técnico da prefeitura entender o erro em português
        'lte' => 'Os slots instalados não podem ser superiores ao total de slots comprados.',
    ]),

            DatePicker::make('purchased_at')
                ->label('Data de Compra')
                ->native(false)
                ->displayFormat('d/m/Y'),

            DatePicker::make('expires_at')
                ->label('Data de Expiração')
                ->native(false)
                ->displayFormat('d/m/Y'),

            Textarea::make('notes')
                ->label('Notas / Observações Adicionais')
                ->columnSpanFull()
                ->rows(3),
        ])
        // DIZEMOS AO FORMULÁRIO QUE ELE SE COMPORTA EM 2 COLUNAS POR PADRÃO:
        ->columns(2); 
}

public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('software_name')
                    ->label('Software')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Localização')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slots_usage')
                    ->label('Slots (Usados / Total)')
                    ->state(fn ($record): string => "{$record->used_slots} / {$record->total_slots}")
                    ->badge()
                    ->color(fn ($record): string => 
                        $record->used_slots >= $record->total_slots ? 'danger' : 'success'
                    ),

                TextColumn::make('expires_at')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Vitalícia')
                    ->badge()
                    ->color(function ($state): string {
                        if (!$state) return 'success';
                        
                        $dataVencimento = Carbon::parse($state);
                        if ($dataVencimento->isPast()) return 'danger';
                        if ($dataVencimento->diffInDays(Carbon::now()) <= 30) return 'warning';
                        
                        return 'gray';
                    }),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Filtrar por Departamento')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('expiradas')
                    ->label('Mostrar Apenas Expiradas')
                    ->query(fn ($query) => $query->where('expires_at', '<', Carbon::now())),
            ])
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
            // Garante que o mapeamento aponta para a subpasta Pages real
            'index' => \App\Filament\Resources\LicensesResource\Pages\ListLicenses::route('/'),
            'create' => \App\Filament\Resources\LicensesResource\Pages\CreateLicense::route('/create'),
            'edit' => \App\Filament\Resources\LicensesResource\Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}
