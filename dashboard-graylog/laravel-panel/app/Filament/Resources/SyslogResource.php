<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyslogsResource\Pages;
use App\Models\Syslog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SyslogResource extends Resource
{
    protected static ?string $model = Syslog::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationLabel(): string
    {
        return 'Monitorização de Logs';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Logs do Sistema';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('received_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('event_id')
                    ->label('ID Evento')
                    ->fontFamily('mono')
                    ->searchable(),

                TextColumn::make('mensagem_formatada')
                    ->label('Descrição do Alerta / Atividade')
                    ->wrap()
                    ->searchable(query: function ($query, $search) {
                        return $query->where('username', 'like', "%{$search}%")
                                     ->orWhere('ip_address', 'like', "%{$search}%")
                                     ->orWhere('workstation', 'like', "%{$search}%"); // Alargado à pesquisa global
                    }),

                TextColumn::make('ip_address')
                    ->label('IP Origem')
                    ->copyable(),

                TextColumn::make('hostname')
                    ->label('Máquina')
                    ->toggleable(isToggledHiddenByDefault: true), // Esconde por defeito para dar prioridade à Workstation

                // 🆕 Nova coluna estruturada vinda do Windows
                TextColumn::make('workstation')
                    ->label('Estação (Workstation)')
                    ->searchable(),

                // 🆕 Nova coluna de domínio/grupo do Windows
                TextColumn::make('workgroup')
                    ->label('Domínio / Grupo')
                    ->toggleable(),

                TextColumn::make('severity')
                    ->label('Severidade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'EMERGENCY' => 'emergency',
                        'CRITICAL' => 'critical',
                        'WARNING'  => 'warning',
                        'SUCCESS'  => 'success',
                        'INFO'     => 'info',
                        default    => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'EMERGENCY' => 'heroicon-m-x-circle',
                        'CRITICAL' => 'heroicon-m-exclamation-triangle',
                        'WARNING'  => 'heroicon-m-bell',
                        'SUCCESS'  => 'heroicon-m-check-circle',
                        'INFO'     => 'heroicon-m-information-circle',
                        default    => 'heroicon-m-question-mark-circle',
                    }),
            ])
            ->defaultSort('received_at', 'desc')
            ->filters([
                SelectFilter::make('severity')
                    ->label('Filtrar por Severidade')
                    ->options([
                    'EMERGENCY' => '🚨 Emergência', 
                    'CRITICAL' => '🚨 Crítico',
                    'WARNING'  => '⚠️ Aviso',
                    'SUCCESS'  => '✅ Sucesso',
                    'INFO'     => 'ℹ️ Informação',
                    ]),

                SelectFilter::make('event_id')
                    ->label('Tipo de Evento')
                    ->options([
                        1149 => '1149 - Tentativa de Logon Interativo',
                        1249 => '1249 - Tentativa de Logon de Rede',
                        2149 => '2149 - Tentativa de Logon Remoto',
                        3149 => '3149 - Tentativa de Logon de Serviço',
                        4624 => '4624 - Logon Bem Sucedido',
                        4625 => '4625 - Falha de Autenticação',
                        4656 => '4656 - Tentativa de Acesso a Objeto',
                        4740 => '4740 - Conta Bloqueada',
                        4768 => '4768 - Ticket de Logon Kerberos Solicitado',
                        4769 => '4769 - Ticket de Serviço Kerberos Solicitado',
                        4946 => '4946 - Regra de Firewall Permitida',
                        4947 => '4947 - Regra de Firewall Negada',
                        4957 => '4957 - Conexão Rejeitada pela Firewall',
                        5140 => '5140 - Acesso a Compartilhamento de Arquivos',
                        5142 => '5142 - Acesso a Compartilhamento de Arquivos Negado',
                        5152 => '5152 - Bloqueio de Firewall',
                        2004 => '2004 - Regra de Firewall Alterada',

                    ]),    

                Filter::make('received_at')
                    ->label('Intervalo de Datas')
                    ->form([
                        DatePicker::make('desde')->label('Desde o dia')->displayFormat('d/m/Y'),
                        DatePicker::make('ate')->label('Até ao dia')->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'], fn (Builder $query, $date): Builder => $query->whereDate('received_at', '>=', $date))
                            ->when($data['ate'], fn (Builder $query, $date): Builder => $query->whereDate('received_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['desde'] ?? null) $indicators[] = 'Desde ' . \Carbon\Carbon::parse($data['desde'])->format('d/m/Y');
                        if ($data['ate'] ?? null) $indicators[] = 'Até ' . \Carbon\Carbon::parse($data['ate'])->format('d/m/Y');
                        return $indicators;
                    }),
            ])
            ->actions([
                ViewAction::make()->slideOver(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar para Excel (xlsx)')
                        ->icon('heroicon-m-table-cells')
                        ->color('success'),
                    
                    BulkAction::make('exportarPDF')
                        ->label('Exportar para PDF')
                        ->icon('heroicon-m-document-text')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $html = "
                            <style>
                                body { font-family: sans-serif; font-size: 11px; color: #333; }
                                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                                th { background-color: #f2f2f2; font-weight: bold; }
                                .header { text-align: center; margin-bottom: 20px; }
                                .critical { color: red; font-weight: bold; }
                            </style>
                            <div class='header'>
                                <h2>Câmara Municipal de Oliveira do Hospital</h2>
                                <h3>Relatório Técnico Forense de Auditoria de Syslogs</h3>
                                <p>Gerado em: " . now()->format('d/m/Y H:i:s') . "</p>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>ID Evento</th>
                                        <th>Severidade</th>
                                        <th>Utilizador</th>
                                        <th>IP Origem</th>
                                        <th>Estação (Workstation)</th>
                                        <th>Domínio/Grupo</th>
                                    </tr>
                                </thead>
                                <tbody>";

                            foreach ($records as $record) {
                                $isCritical = $record->severity === 'CRITICAL' ? "class='critical'" : "";
                                $html .= "
                                    <tr>
                                        <td>{$record->received_at?->format('d/m/Y H:i:s')}</td>
                                        <td>{$record->event_id}</td>
                                        <td {$isCritical}>{$record->severity}</td>
                                        <td>" . ($record->username ?? 'N/A') . "</td>
                                        <td>{$record->ip_address}</td>
                                        <td>" . ($record->workstation ?? $record->hostname ?? 'N/A') . "</td>
                                        <td>" . ($record->workgroup ?? 'N/A') . "</td>
                                    </tr>";
                            }

                            $html .= "</tbody></table>";

                            return response()->streamDownload(function () use ($html) {
                                echo "<script>window.onload = function() { window.print(); }</script>" . $html;
                            }, 'relatorio-syslogs-' . now()->format('Y-m-d-His') . '.html');
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSyslogs::route('/'),
        ];
    }

    public static function infolist(Schema $schema): Schema {
        return $schema->schema([
            Tabs::make('Detalhes do Log')
            ->tabs([
                Tabs\Tab::make('Análise Forense')
                ->icon('heroicon-m-magnifying-glass-circle')
                ->schema([
                    TextEntry::make('mensagem_formatada')
                    ->label('Resultado do Alerta')
                    ->weight('bold')
                    ->color(fn ($record) => match ($record->severity) {
                        'EMERGENCY' => '#ff0000',
                        'CRITICAL' => '#ff7300',
                        'WARNING'  => '#fff700',
                        'SUCCESS'  => '#00ff62',
                        'INFO'     => '#0062ff',
                        default    => 'gray',
                    }),
                    Grid::make(3)->schema([
                        TextEntry::make('event_id')->label('ID do Evento')->fontFamily('mono'),
                        TextEntry::make('received_at')->label('Data/Hora')->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('ip_address')->label('IP de Origem')->copyable(),
                        TextEntry::make('mac_address')->label('Endereço MAC')->fontFamily('mono'),
                        TextEntry::make('username')->label('Utilizador Envolvido'),
                        TextEntry::make('workstation')->label('Estação Atacada (Workstation)')->weight('bold'),
                        TextEntry::make('workgroup')->label('Domínio / Workgroup Windows'),
                        TextEntry::make('hostname')->label('Hostname Técnico (Docker/DNS)'),
                    ]),
                ]),
                Tabs\Tab::make('metadados XML Bruto')
                ->icon('heroicon-m-command-line')
                ->schema([
                    TextEntry::make('message')
                    ->label('Evidencia Nativa do Windows Event Viewer')
                    ->fontFamily('mono')
                    ->extraAttributes([
                        'class' => 'bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-xs max-h-96'
                    ]),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}