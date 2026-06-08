<?php

namespace App\Filament\Resources\SyslogsResource\Widgets;

use App\Models\Syslog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Support\Colors\Color;

class SyslogPieChart extends ChartWidget
{
    // O cabeçalho adapta-se dinamicamente ao filtro selecionado
    public function getHeading(): string
    {
        return $this->filter === 'dia' 
            ? 'Distribuição por Severidade (Últimos 7 Dias)' 
            : 'Distribuição por Severidade (Hoje)';
    }

    protected function getExtraAttributes(): array
{
    return [
        'class' => 'transition-all duration-500 ease-in-out',
    ];
}

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '10s';

    // 1. Cria o dropdown de seleção nativo do Filament
    protected function getFilters(): ?array
    {
        return [
            'hora' => 'Hoje',
            'dia' => 'Últimos 7 Dias',
        ];
    }

    protected function getData(): array
    {
        // Captura o filtro ativo (padrão é 'hora', que significa Hoje)
        $filtroAtivo = $this->filter ?? 'hora';

        // 2. Decide o período de tempo baseado no filtro
        if ($filtroAtivo === 'dia') {
            $inicioPeriodo = Carbon::today()->subDays(6);
            $fimPeriodo = Carbon::tomorrow()->subSecond();
        } else {
            $inicioPeriodo = Carbon::today();
            $fimPeriodo = Carbon::tomorrow()->subSecond();
        }

        // 3. Executa a query ao Postgres agrupando por severidade no período definido
        $severidades = Syslog::select('severity', DB::raw('count(*) as total'))
            ->whereBetween('received_at', [$inicioPeriodo, $fimPeriodo])
            ->where('is_exception', false) // Exclui os logs marcados como exceção
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->toArray();

        // 4. Garante a ordem exata das fatias da pizza e das legendas
        $labels = ['INFO', 'WARNING', 'CRITICAL', 'EMERGENCY', 'SUCCESS', 'AUDIT'];
        $data = [
            $severidades['INFO'] ?? 0,
            $severidades['WARNING'] ?? 0,
            $severidades['CRITICAL'] ?? 0,
            $severidades['EMERGENCY'] ?? 0,
            $severidades['SUCCESS'] ?? 0,
            $severidades['AUDIT'] ?? 0,
        ];

        $colors = [
            Color::Blue[500],
            Color::Yellow[500],
            Color::Orange[500],
            Color::Red[600],
            Color::Emerald[600],
            Color::Indigo[500],
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total de Logs',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#1e293b', 
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
{
    return [
        'animation' => [
            'duration' => 1200, // Um pouco mais lento para dar aquele efeito cinematográfico
            'easing' => 'easeOutBounce', // Dá um pequeno "ressalto" elástico muito profissional no fim
            'animateRotate' => true, // Faz a pizza rodar ao aparecer
            'animateScale' => true,  // Faz a pizza crescer do centro para fora
        ],
        'plugins' => [
            'legend' => [
                'position' => 'bottom', // Coloca as legendas em baixo para dar mais espaço
            ],
        ],
    ];
}
}