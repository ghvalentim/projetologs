<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
// Usamos o container genérico de componentes de Schema que o seu core possui
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{ 
    protected static string $layout = 'filament-panels::components.layout.index'; // Usa o layout padrão do painel para manter a consistência visual
    protected Width | string | null $maxWidth = '7xl';    
    // Usa o layout padrão do painel para manter a consistência visual
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ProfileTabs')
                    ->tabs([
                        
                        // ABA 1: VISÃO GERAL E DADOS
                        Tabs\Tab::make('Visão Geral')
                            ->icon('heroicon-m-user-circle')
                            ->schema([
                                
                                // Usamos um Group com colunas para fazer a divisão responsiva lado a lado de forma nativa e segura
                                Group::make([
                                    
                                    // Bloco Esquerdo: O cartão visual com o avatar
                                    Section::make([
                                        ViewField::make('avatar_card')
                                            ->view('filament.widgets.profile-avatar-card'),
                                    ])->columnSpan(1),

                                    // Bloco Direito: Formulário com os dados cadastrais
                                    Section::make('Informações de Identificação')
                                        ->description('Mantenha os seus dados de contacto atualizados para auditoria no SGLS.')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Nome Completo')
                                                ->required()
                                                ->maxLength(255),

                                            TextInput::make('email')
                                                ->label('Endereço de E-mail (Identificador)')
                                                ->disabled()
                                                ->helperText('O e-mail institucional não pode ser alterado diretamente pelo utilizador.'),
                                        ])->columnSpan(2),

                                ])->columns([
                                    'sm' => 1,
                                    'md' => 3, // O card ocupa 1/3 e o form ocupa 2/3 do ecrã
                                ]),
                                
                            ]),

                        // ABA 2: CONFIGURAÇÕES DE SEGURANÇA
                        Tabs\Tab::make('Segurança da Conta')
                            ->icon('heroicon-m-key')
                            ->schema([
                                Section::make('Alterar Palavra-passe')
                                    ->description('Certifique-se de que utiliza uma combinação forte para proteger o acesso aos servidores e licenças do município.')
                                    ->schema([
                                        $this->getPasswordFormComponent(),
                                        $this->getPasswordConfirmationFormComponent(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}