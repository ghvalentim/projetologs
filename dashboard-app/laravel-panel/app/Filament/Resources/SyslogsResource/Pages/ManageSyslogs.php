<?php

namespace App\Filament\Resources\SyslogsResource\Pages;

use App\Filament\Resources\SyslogResource;
use App\Filament\Resources\SyslogsResource\Widgets\SyslogStats;
use App\Filament\Resources\SyslogsResource\Widgets\SyslogChart;
use App\Filament\Resources\SyslogsResource\Widgets\SyslogPieChart;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSyslogs extends ManageRecords
{
    protected static string $resource = SyslogResource::class;

    /**
     * Removemos o CreateAction para o utilizador não conseguir
     * inserir logs falsos manualmente pelo painel do Tauri.
     */
    protected function getHeaderActions(): array
    {
        return [
            // Mantido vazio de forma segura
        ];
    }

    /**
     * Gráficos e painéis estatísticos de suporte ao teu estágio de TPSI
     */
    protected function getHeaderWidgets(): array
    {
        return [
            SyslogStats::class,
            SyslogChart::class,
            SyslogPieChart::class,
        ];
    }

    /**
     * Layout responsivo para os teus gráficos dividirem o ecrã no Tauri
     */
    public function getColumns(): int | string | array 
    {
        return ['sm' => 1, 'lg' => 2];
    }
}