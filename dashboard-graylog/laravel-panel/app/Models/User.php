<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable; // O motor de notificações nativo do Laravel

    protected $fillable = ['name', 'email', 'password'];

    /**
     * Relação polimórfica que resolve o erro "undefined method notifications()"
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable')
                    ->orderBy('created_at', 'desc');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}