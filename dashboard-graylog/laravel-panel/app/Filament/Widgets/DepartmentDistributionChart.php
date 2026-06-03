<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\ChartWidget;

class DepartmentDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Licenças Ativas por Ponto de Atendimento';

    protected function getData(): array
    {
        $dados = Department::withCount('licenses')
            ->has('licenses')
            ->get();

        $labels = $dados->pluck('name')->toArray();
        $valores = $dados->pluck('licenses_count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade de Licenças',
                    'data' => $valores,
                    'backgroundColor' => '#8b5cf6', // Roxo do Tailwind
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    // Define que este gráfico ocupa metade da largura (6 de 12 colunas) em ecrãs grandes
protected static ?int $sort = 4; // Ordem de aparição
protected int | string | array $columnSpan = [
    'lg' => 1,
];
}