<?php

namespace App\Filament\Resources\LicensesResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.name')
                    ->searchable(),
                TextColumn::make('software_name')
                    ->searchable(),
                TextColumn::make('license_key')
                    ->searchable(),
                TextColumn::make('supplier')
                    ->searchable(),
                TextColumn::make('total_slots')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('used_slots')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('purchased_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
