<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use App\Models\License;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class LicenseAlertsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '15s';
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // 1. Contagem de Licenças já vencidas
        $vencidas = License::where('expires_at', '<', Carbon::now())->count();

        // 2. Contagem de Licenças a vencer nos próximos 30 dias
        $aVencer = License::where('expires_at', '>=', Carbon::now())
            ->where('expires_at', '<=', Carbon::now()->addDays(30))
            ->count();

        // 3. Total de pontos de atendimento cadastrados
        $totalDepartamentos = Department::count();

        return [
            Stat::make('Licenças Vencidas', $vencidas)
                ->description($vencidas > 0 ? 'Ação necessária imediata' : 'Tudo regularizado')
                ->descriptionIcon($vencidas > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($vencidas > 0 ? 'danger' : 'success'),

            Stat::make('Críticas (Vencem em 30 dias)', $aVencer)
                ->description('Alertas de proximidade')
                ->descriptionIcon('heroicon-m-clock')
                ->color($aVencer > 0 ? 'warning' : 'gray'),

            Stat::make('Pontos de Atendimento', $totalDepartamentos)
                ->description('Escolas, Campis e Secretarias')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
        ];
    }
}