<?php

namespace App\Observers;

use App\Models\Syslog;
use App\Models\User;
use Filament\Notifications\Notification;

class SyslogObserver
{
    public function created(Syslog $syslog): void
    {
        if (in_array($syslog->severity, ['CRITICAL', 'EMERGENCY'])) {
            
            // Pega todos os administradores que vão receber o alerta
            $users = User::all();

            $titulo = $syslog->severity === 'EMERGENCY' 
                ? '🚨 EMERGENCY: INTRUSÃO ATIVA!' 
                : '⚠️ ALERTA CRÍTICO: Falha de Segurança';

            // Monta o objeto de notificação do Filament
            $filamentNotification = Notification::make()
                ->title($titulo)
                ->icon($syslog->severity === 'EMERGENCY' ? 'heroicon-o-fire' : 'heroicon-o-shield-exclamation')
                ->body("O dispositivo **{$syslog->hostname}** gerou um log de nível **{$syslog->severity}**.")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Ir para a Ocorrência')
                        ->button()
                        ->url(fn () => "/admin/syslogs"), 
                ])
                ->color($syslog->severity === 'EMERGENCY' ? 'danger' : 'warning')
                ->duration(15000);

            // Dispara para cada utilizador usando o método oficial .notify()
            foreach ($users as $recipient) {
                $recipient->notify($filamentNotification->toDatabase());
            }
        }
    }
}