<?php

// 🔴 ALINHADO: Fica no namespace padrão das Resources
namespace App\Filament\Resources;

// 🔴 ALINHADO: Importa as páginas a partir da tua pasta real SyslogsResource
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
use Filament\Schemas\Schema; // Classe pai unificada do Filament v3

class SyslogResource extends Resource
{
    // Vinculação obrigatória ao modelo Eloquent correto
    protected static ?string $model = Syslog::class;

    /**
     * Define o ícone da barra lateral usando os métodos nativos do v3 (BackedEnum/string).
     */
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shield-check';
    }

    /**
     * Texto apresentado no menu de navegação lateral.
     */
    public static function getNavigationLabel(): string
    {
        return 'Monitorização de Logs';
    }

    /**
     * Rótulo plural utilizado nos cabeçalhos das tabelas e breadcrumbs.
     */
    public static function getPluralModelLabel(): string
    {
        return 'Logs do Sistema';
    }

    /**
     * Configuração completa da tabela de listagem otimizada para o ecrã do Tauri.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Data e Hora de receção do Log
                TextColumn::make('received_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                // ID do Evento Nativo do Windows (ex: 4625)
                TextColumn::make('event_id')
                    ->label('ID Evento')
                    ->fontFamily('mono')
                    ->searchable(),

                // 🧠 Invocação do Acessor inteligente formatado no Laravel em PT-PT
                TextColumn::make('mensagem_formatada')
                    ->label('Descrição do Alerta / Atividade')
                    ->wrap() // Quebra de linha automática para manter a responsividade no Tauri
                    ->searchable(query: function ($query, $search) {
                        // Alarga a pesquisa global para procurar pelo username ou IP dentro do contexto
                        return $query->where('username', 'like', "%{$search}%")
                                     ->orWhere('ip_address', 'like', "%{$search}%");
                    }),

                // Endereço IP capturado pelo script Go
                TextColumn::make('ip_address')
                    ->label('IP Origem')
                    ->copyable(), // Permite copiar com um clique no painel

                // Endereço MAC capturado na rede
                TextColumn::make('mac_address')
                    ->label('Endereço MAC')
                    ->fontFamily('mono'),

                // Nome da Máquina de origem
                TextColumn::make('hostname')
                    ->label('Máquina'),

                // 🎨 Embelezamento visual: Distinção de severidade usando badges nativos do v3
                TextColumn::make('severity')
                    ->label('Severidade')
                    ->badge() // Transforma a coluna num badge com cantos arredondados
                    ->color(fn (string $state): string => match ($state) {
                        'CRITICAL' => 'danger',  // Vermelho
                        'WARNING'  => 'warning', // Amarelo
                        'INFO'     => 'success', // Verde
                        default    => 'gray',    // Cinzento padrão
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'CRITICAL' => 'heroicon-m-exclamation-triangle',
                        'WARNING'  => 'heroicon-m-bell',
                        'INFO'     => 'heroicon-m-information-circle',
                        default    => 'heroicon-m-question-mark-circle',
                    }),
            ])
            ->defaultSort('received_at', 'desc') // Logs mais recentes sempre no topo
            ->filters([
                SelectFilter::make('severity')
                    ->label('Filtrar por Severidade')
                    ->options([
                        'CRITICAL' => '🚨 Crítico',
                        'WARNING'  => '⚠️ Aviso',
                        'INFO'     => 'ℹ️ Informação',
                    ]),

                // 2. Filtro por ID do Evento (Comum para auditoria rápida)
                SelectFilter::make('event_id')
                    ->label('Tipo de Evento')
                    ->options([
                        4625 => '4625 - Falha de Autenticação',
                        5152 => '5152 - Bloqueio de Firewall',
                        2004 => '2004 - Regra de Firewall Alterada',
                    ]),

                // 3. Filtro por Intervalo de Datas Avançado
                Filter::make('received_at')
                    ->label('Intervalo de Datas')
                    ->form([
                        DatePicker::make('desde')
                            ->label('Desde o dia')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('ate')
                            ->label('Até ao dia')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_at', '>=', $date),
                            )
                            ->when(
                                $data['ate'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        // Exibe etiquetas bonitas por cima da tabela indicando os filtros ativos
                        $indicators = [];
                        if ($data['desde'] ?? null) {
                            $indicators[] = 'Desde ' . \Carbon\Carbon::parse($data['desde'])->format('d/m/Y');
                        }
                        if ($data['ate'] ?? null) {
                            $indicators[] = 'Até ' . \Carbon\Carbon::parse($data['ate'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                // Ações individuais por linha de registo (ex: Visualizar detalhes do XML bruto)
                ViewAction::make()->slideOver(),
            ])
            ->bulkActions([
                // Estrutura obrigatória do v3 para ações em lote no rodapé
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
                            // Geramos um HTML limpo em formato de relatório de auditoria
                            $html = "
                            <style>
                                body { font-family: sans-serif; font-size: 12px; color: #333; }
                                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                th { bg-color: #f2f2f2; font-weight: bold; }
                                .header { text-align: center; margin-bottom: 30px; }
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
                                        <th>Máquina</th>
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
                                        <td>{$record->hostname}</td>
                                    </tr>";
                            }

                            $html .= "</tbody></table>";

                            // Invocamos a janela de impressão nativa do motor de renderização do Tauri/Browser
                            // Isto gera um PDF perfeito de forma instantânea sem pesar no servidor Docker!
                            return response()->streamDownload(function () use ($html) {
                                echo "<script>window.onload = function() { window.print(); }</script>" . $html;
                            }, 'relatorio-syslogs-' . now()->format('Y-md-His') . '.html');
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Mapeamento de rotas internas do Filament.
     */
    public static function getPages(): array
    {
        return [
            // 🔴 ALINHADO: Aponta perfeitamente para o mapeamento da tua pasta real
            'index' => Pages\ManageSyslogs::route('/'),
        ];
    }

    public static function infolist(Schema $schema): Schema {
        return $schema->schema([
            Tabs::make('Detalhes do Log')
            ->tabs([Tabs\Tab::make('Análise Forense')
            ->icon('heroicon-m-magnifying-glass-circle')
            ->schema([
                TextEntry::make('mensagem_formatada')
                ->label('Resultado do Alerta')
                ->weight('bold')
                ->color(fn ($record) => match ($record->severity) {
                    'CRITICAL' => 'danger',
                    'WARNING'  => 'warning',
                    'INFO'     => 'success',
                    default    => 'gray',
                }),
                Grid::make(2)->schema([
                    TextEntry::make('event_id')->label('ID do Evento')->fontFamily('mono'),
                    TextEntry::make('received_at')->label('Data/Hora')->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('ip_address')->label('IP de Origem')->copyable(),
                    TextEntry::make('mac_address')->label('Endereço MAC')->fontFamily('mono'),
                    TextEntry::make('hostname')->label('Máquina de Origem'),
                    TextEntry::make('username')->label('Utilizador Envolvido'),
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