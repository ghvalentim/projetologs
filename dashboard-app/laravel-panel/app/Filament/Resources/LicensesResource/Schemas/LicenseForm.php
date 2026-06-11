<?php

namespace App\Filament\Resources\LicensesResource\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LicenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->required(),
                TextInput::make('software_name')
                    ->required(),
                TextInput::make('license_key'),
                TextInput::make('supplier'),
                TextInput::make('total_slots')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('used_slots')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('purchased_at'),
                DatePicker::make('expires_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
