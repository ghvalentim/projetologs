<?php

namespace App\Filament\Resources\DepartmentsResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('type'),
                TextInput::make('responsible_person'),
                TextInput::make('contact_email')
                    ->email(),
            ]);
    }
}
