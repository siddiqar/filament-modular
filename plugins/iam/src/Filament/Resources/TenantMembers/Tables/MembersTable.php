<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenants.name')
                    ->label('Organizations')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenants_count')
                    ->label('Total Organizations')
                    ->counts('tenants')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('User Since')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Could add filters here if needed
            ])
            ->actions([
                // Admin panel - no actions, view-only
                // To manage members, use TenantResource -> Members tab
            ])
            ->bulkActions([
                // No bulk actions in admin panel
            ])
            ->defaultSort('created_at', 'desc');
    }
}
