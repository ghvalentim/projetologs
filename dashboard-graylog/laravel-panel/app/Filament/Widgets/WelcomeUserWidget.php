<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeUserWidget extends Widget
{
    // Define que a estrutura visual deste widget virá de uma View do Blade
    protected  string $view = 'filament.widgets.welcome-user-widget';

    // Garante que ele assume o topo da página, antes dos cartões de estatísticas
    protected static ?int $sort = 1;

    // Faz o widget ocupar a largura total do ecrã
    protected int | string | array $columnSpan = 'full';
}