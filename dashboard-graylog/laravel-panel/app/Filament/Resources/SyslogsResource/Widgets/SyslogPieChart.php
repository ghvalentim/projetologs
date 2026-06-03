<?php

namespace App\Filament\Resources\SyslogsResource\Widgets;

use App\Models\Syslog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyslogPieChart extends ChartWidget
{
    protected  ?string $heading = 'Distribuição por Severidade (Hoje)';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = '10s';

    protected function getData(): array
    {
        // Filtra apenas os logs do dia de hoje
        $severidades = Syslog::select('severity', DB::raw('count(*) as total'))
            ->where('received_at', '>=', Carbon::today())
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->toArray();

        // Garante uma ordem bonita para a legenda e mapeia os dados
        $labels = ['INFO', 'WARNING', 'ERROR', 'CRITICAL'];
        $data = [
            $severidades['INFO'] ?? 0,
            $severidades['WARNING'] ?? 0,
            $severidades['ERROR'] ?? 0,
            $severidades['CRITICAL'] ?? 0,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total de Logs',
                    'data' => $data,
                    // Cores correspondentes: Verde, Laranja, Vermelho Claro, Vermelho Escuro
                    'backgroundColor' => [
                        '#10b981', // emerald-500
                        '#f59e0b', // amber-500
                        '#ef4444', // red-500
                        '#b91c1c', // red-700
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // Define o tipo como 'pie' (pizza)
        return 'pie';
    }
}