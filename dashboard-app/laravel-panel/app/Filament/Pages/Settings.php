<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
// 🎯 OS NOVOS IMPORTS OFICIAIS DE LAYOUT DA VERSÃO ATUAL:
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use UnitEnum;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $title = 'Definições do Sistema';
    protected static ?string $navigationLabel = 'Definições';
    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected    string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'primary_color'           => Setting::where('key', 'theme_primary_color')->first()?->value ?? 'blue',
            'syslog_polling_interval' => Setting::where('key', 'syslog_polling_interval')->first()?->value ?? '5',
            'notify_emergency_email'  => (bool) (Setting::where('key', 'notify_emergency_email')->first()?->value ?? true),
            'retention_months'        => Setting::where('key', 'retention_months')->first()?->value ?? '6',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('SettingsTabs')
                    ->tabs([
                        // ABA 1: PERSONALIZAÇÃO VISUAL
                        Tabs\Tab::make('Visual')
                            ->label('Aspeto Visual')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Select::make('primary_color')
                                    ->label('Cor Principal do Painel')
                                    ->options([
                                        'blue' => 'Azul (Padrão)',
                                        'emerald' => 'Esmeralda (Verde)',
                                        'amber' => 'Âmbar (Laranja/Amarelo)',
                                        'rose' => 'Rosa',
                                        'indigo' => 'Índigo',
                                        'violet' => 'Violeta',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),

                        // ABA 2: ALERTAS E NOTIFICAÇÕES
                        Tabs\Tab::make('Notifications')
                            ->label('Alertas & Notificações')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Section::make('Comportamento do Painel')
                                    ->description('Ajuste como o painel reage em tempo real.')
                                    ->schema([
                                        TextInput::make('syslog_polling_interval')
                                            ->label('Intervalo de Polling do Sininho (segundos)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(60)
                                            ->required(),
                                    ]),

                                Section::make('Canais de Alerta')
                                    ->description('Determine para onde enviar os alertas críticos além do painel.')
                                    ->schema([
                                        Toggle::make('notify_emergency_email')
                                            ->label('Enviar e-mail automático em caso de EMERGENCY')
                                            ->helperText('Notifica o administrador do sistema imediatamente quando uma intrusão for detetada.')
                                            ->default(true),
                                    ]),
                            ]),

                        // ABA 3: MANUTENÇÃO DO SISTEMA
                        Tabs\Tab::make('System')
                            ->label('Manutenção de Logs')
                            ->icon('heroicon-o-server')
                            ->schema([
                                Select::make('retention_months')
                                    ->label('Período de Retenção de Logs')
                                    ->helperText('Logs mais antigos que este período serão expurgados da base de dados automaticamente.')
                                    ->options([
                                        '1' => '1 Mês',
                                        '3' => '3 Meses',
                                        '6' => '6 Meses',
                                        '12' => '1 Ano',
                                        '0' => 'Nunca apagar (Manter histórico total)',
                                    ])
                                    ->native(false)
                                    ->required(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Configurações')
                ->submit('form')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $settingsToUpdate = [
            'theme_primary_color'     => $state['primary_color'],
            'syslog_polling_interval' => $state['syslog_polling_interval'],
            'notify_emergency_email'  => $state['notify_emergency_email'] ? '1' : '0',
            'retention_months'        => $state['retention_months'],
        ];

        foreach ($settingsToUpdate as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Notification::make()
            ->title('Configurações guardadas!')
            ->body('Todas as preferências do sistema foram atualizadas.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }
}