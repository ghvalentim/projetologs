<?php

namespace App\Observers;

use App\Models\Syslog;
use App\Models\User;
use Filament\Notifications\Notification;

class SyslogObserver
{
    /**
     * Handle the Syslog "created" event.
     */
    public function created(Syslog $syslog): void
    {
        if ($syslog->severity === 'CRITICAL') {
            $users = User::all();

            foreach ($users as $user) {
                Notification::make()
                ->title('Alerta Crítico de Segurança')
                ->icon('heroicon-o-shield-exclamation')
                ->iconColor('danger')
                ->body($syslog->message)
                ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                        ->button()->label('Ver Detalhes')
                        ->url(fn () => route('filament.admin.resources.syslogs.index', ['tableSearch' => $syslog->ip_address]))
                    ])
                ->danger()
                ->sendToDatabase($user);
            }
        }
    }

    /**
     * Handle the Syslog "updated" event.
     */
    public function updated(Syslog $syslog): void
    {
        //
    }

    /**
     * Handle the Syslog "deleted" event.
     */
    public function deleted(Syslog $syslog): void
    {
        //
    }

    /**
     * Handle the Syslog "restored" event.
     */
    public function restored(Syslog $syslog): void
    {
        //
    }

    /**
     * Handle the Syslog "force deleted" event.
     */
    public function forceDeleted(Syslog $syslog): void
    {
        //
    }
}
