<?php

namespace App\Filament\Resources\SyslogsResource\Widgets;

use App\Models\Syslog;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SyslogStats extends BaseWidget
{
    // Define que o widget vai atualizar os dados sozinho a cada 10 segundos
    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        // Define o limite de 24 horas atrás
        $atras24h = Carbon::now()->subDay();

        // 1. Total de Logs nas últimas 24h
        $totalLogs = Syslog::where('received_at', '>=', $atras24h)->count();

        // 2. Total de Erros/Críticos nas últimas 24h
        $totalErros = Syslog::where('received_at', '>=', $atras24h)
            ->whereIn('severity', ['ERROR', 'CRITICAL'])
            ->count();

        // 3. Descobrir o IP mais ativo
        $ipMaisAtivoRow = Syslog::select('ip_address')
            ->selectRaw('COUNT(*) as total')
            ->where('received_at', '>=', $atras24h)
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->first();

        $ipMaisAtivo = $ipMaisAtivoRow ? "{$ipMaisAtivoRow->ip_address} ({$ipMaisAtivoRow->total})" : 'Nenhum dado';

        return [
            Stat::make('Logs (Últimas 24h)', $totalLogs)
                ->description('Total de eventos capturados')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Alertas Críticos', $totalErros)
                ->description('Erros e falhas críticas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($totalErros > 0 ? 'danger' : 'success'),

            Stat::make('Origem Mais Ativa', $ipMaisAtivo)
                ->description('Dispositivo com maior volumetria')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('warning'),
        ];
    }
}