<?php

namespace App\Filament\Resources\SyslogsResource\Widgets;

use App\Models\Syslog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyslogChart extends ChartWidget
{
    // O cabeçalho agora adapta-se dinamicamente ao filtro selecionado
    public function getHeading(): string
    {
        return $this->filter === 'dia' 
            ? 'Volumetria de Logs (Últimos 7 Dias)' 
            : 'Volumetria de Logs (Hoje por Hora)';
    }
    
    protected ?string $maxHeight = '300px';
    protected ?string $pollingInterval = '10s';

    // 1. Define as opções do filtro que vão aparecer no canto do gráfico
    protected function getExtraAttributes(): array
{
    return [
        'class' => 'transition-all duration-500 ease-in-out',
    ];
}
    
    protected function getFilters(): ?array
    {
        return [
            'hora' => 'Hoje por Hora',
            'dia' => 'Últimos 7 Dias',
        ];
    }

    protected function getData(): array
    {
        // O Filament guarda a opção escolhida na propriedade $this->filter
        $filtroAtivo = $this->filter ?? 'hora';

        $labels = [];
        $dadosData = [];

        if ($filtroAtivo === 'dia') {
            // --- VISUALIZAÇÃO POR DIA (Últimos 7 dias) ---
            $dadosPostgres = $this->getLogsPorDia();

            // Monta os labels com os últimos 7 dias estruturados (ex: "03/06", "02/06"...)
            for ($i = 6; $i >= 0; $i--) {
                $dataAlvo = Carbon::today()->subDays($i);
                $chaveFormatada = $dataAlvo->format('Y-m-d'); // Chave para buscar no array do Postgres
                
                $labels[] = $dataAlvo->format('d/m'); // Label bonito para o utilizador ver
                $dadosData[] = $dadosPostgres[$chaveFormatada] ?? 0;
            }
        } else {
            // --- VISUALIZAÇÃO POR HORA (Hoje) ---
            $dadosPostgres = $this->getLogsPorHoraHoje();

            // Monta as 24 horas de 00h a 23h
            for ($i = 0; $i < 24; $i++) {
                $labels[] = sprintf('%02dh', $i);
                $dadosData[] = $dadosPostgres[$i] ?? 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade de Logs',
                    'data' => $dadosData,
                    'backgroundColor' => $filtroAtivo === 'dia' ? '#10b981' : '#3b82f6', // Verde para dias, Azul para horas
                    'borderColor' => $filtroAtivo === 'dia' ? '#059669' : '#2563eb', 
                    'borderRadius' => 4, 
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Query para o filtro por HORA (Apenas o dia de hoje)
     */
    protected function getLogsPorHoraHoje(): array
    {
        $inicioDia = Carbon::today();
        $fimDia = Carbon::tomorrow()->subSecond();

        return Syslog::select(
                DB::raw("DATE_PART('hour', received_at) as hora"),
                DB::raw("COUNT(*) as total")
            )
            ->whereBetween('received_at', [$inicioDia, $fimDia])
            ->groupBy('hora')
            ->pluck('total', 'hora')
            ->toArray();
    }

    /**
     * Query para o filtro por DIA (Últimos 7 dias acumulados)
     */
    protected function getLogsPorDia(): array
    {
        // Pega desde há 6 dias até ao fim do dia de hoje
        $inicioPeriodo = Carbon::today()->subDays(6);
        $fimPeriodo = Carbon::tomorrow()->subSecond();

        return Syslog::select(
                DB::raw("TO_CHAR(received_at, 'YYYY-MM-DD') as dia"),
                DB::raw("COUNT(*) as total")
            )
            ->whereBetween('received_at', [$inicioPeriodo, $fimPeriodo])
            ->groupBy('dia')
            ->pluck('total', 'dia') // Retorna ex: ['2026-06-03' => 14, '2026-06-02' => 8]
            ->toArray();
    }

    protected function getType(): string
    {
        return 'bar';
    }

protected function getOptions(): array
{
    return [
        'animation' => [
            'duration' => 800,
            'easing' => 'easeInOutQuad',
            'animateRotate' => true,
            'animateScale' => false, // Desativar o scale ajuda o Livewire a não quebrar a geometria
        ],
        'transitions' => [
            'active' => [
                'animation' => [
                    'duration' => 800
                ]
            ]
        ],
        'plugins' => [
            'legend' => [
                'position' => 'bottom',
            ],
        ],
    ];
}
}