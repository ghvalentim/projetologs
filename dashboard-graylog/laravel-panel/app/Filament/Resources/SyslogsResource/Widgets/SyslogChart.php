<?php

namespace App\Filament\Resources\SyslogsResource\Widgets;

use App\Models\Syslog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyslogChart extends ChartWidget
{
    protected ?string $heading = 'Volumetria de Logs (Hoje por Hora)';
    
    // Atualiza o gráfico sozinho a cada 10 segundos junto com os cards
    protected ?string $pollingInterval = '10s';

    protected function getData(): array
    {
        // Pega o início e fim do dia de hoje
        $inicioDia = Carbon::today();
        $fimDia = Carbon::tomorrow()->subSecond();

        // Query no Postgres para agrupar logs por hora truncada do dia de hoje
        $logsPorHora = Syslog::select(
                DB::raw("DATE_PART('hour', received_at) as hora"),
                DB::raw("COUNT(*) as total")
            )
            ->whereBetween('received_at', [$inicioDia, $fimDia])
            ->groupBy('hora')
            ->orderBy('hora')
            ->pluck('total', 'hora')
            ->toArray();

        // Monta os labels de 00h até 23h e preenche com zero onde não houver log
        $labels = [];
        $dadosData = [];

        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02dh', $i);
            // Se o Postgres tiver contagem para aquela hora, usa. Se não, é 0.
            $dadosData[] = $logsPorHora[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade de Logs',
                    'data' => $dadosData,
                    'borderColor' => '#3b82f6', 
                   'borderRadius' => 4, ],
                   ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // Tipo 'bar' para gráfico de barras
        return 'bar';
    }
}