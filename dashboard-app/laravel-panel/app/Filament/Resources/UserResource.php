<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash; // 👈 Necessário para esconder a password
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord; // 👈 Necessário para a lógica da password
use Filament\Resources\Pages\EditRecord; // 👈 Necessário para a lógica da passwor
use Filament\Schemas\Schema;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
    
    // 👇 1. Isto cria automaticamente a secção "Configurações" no menu lateral
    protected static string|UnitEnum|null $navigationGroup = 'Sistema';
    protected static ?string $modelLabel = 'Utilizador';
    protected static ?string $pluralModelLabel = 'Utilizadores';

    // 👇 2. O BLOQUEIO DE SEGURANÇA (O segredo está aqui)
    // Se isto retornar 'false', o menu desaparece e o link dá Erro 403.
    public static function canAccess(): bool
    {
        // Se usares uma coluna 'role' em vez de boolean, seria: Auth::user()->role === 'admin'
        // Garante que não damos erro quando não existe sessão (null safe)
        return (Auth::user()?->is_admin ?? false) === true;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Detalhes do Utilizador')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                            
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            // Encripta a password antes de a guardar na BD
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            // Só atualiza a password se o campo não estiver vazio
                            ->dehydrated(fn ($state) => filled($state))
                            // A password só é obrigatória na criação. Na edição é opcional.
                            ->required(fn (string $context): bool => $context === 'create'),
                            
                        Toggle::make('is_admin')
                            ->label('Conceder Privilégios de Administrador')
                            ->onColor('danger') // Fica vermelho para dar ênfase ao perigo
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(), // Mostra um V verde ou um X vermelho
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Podes adicionar filtros aqui futuramente
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
    
    // ... (Mantém o método getPages() original que o Filament gerou no final do ficheiro)
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}