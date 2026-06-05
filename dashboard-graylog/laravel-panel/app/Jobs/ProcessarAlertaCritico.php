<?php

namespace App\Jobs;

use App\Models\Syslog;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessarAlertaCritico implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $logData;

    public function __construct(array $logData)
    {
        $this->logData = $logData;
    }

    public function handle(): void
{
    // Salva o log usando os índices exatos mapeados no Go
    $syslog = Syslog::create([
        'event_id'    => $this->logData['id'] ?? '0',
        'hostname'    => $this->logData['host'] ?? 'UNKNOWN',
        'ip_address'  => $this->logData['ip'] ?? '127.0.0.1',
        'username'    => $this->logData['user'] ?? 'SYSTEM',
        'severity'    => $this->logData['severity'] ?? 'INFO',
        'message'     => $this->logData['msg'] ?? 'Alerta crítico de rede',
        'received_at' => now(),
    ]);

    // Dispara para o Filament
    $recipient = User::first(); 

    if ($recipient && in_array($syslog->severity, ['CRITICAL', 'EMERGENCY'])) {
        $titulo = $syslog->severity === 'EMERGENCY' 
            ? '🚨 EMERGENCY: INTRUSÃO ATIVA!' 
            : '⚠️ ALERTA CRÍTICO: Falha de Segurança';

        \Filament\Notifications\Notification::make()
            ->title($titulo)
            ->icon($syslog->severity === 'EMERGENCY' ? 'heroicon-o-fire' : 'heroicon-o-shield-exclamation')
            ->body("O dispositivo **{$syslog->hostname}** gerou um log crítico.")
            ->color($syslog->severity === 'EMERGENCY' ? 'danger' : 'warning')
            ->sendToDatabase($recipient);
    }
}
}