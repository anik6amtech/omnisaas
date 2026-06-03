<?php

namespace App\Filament\Resources\Workspaces\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkspacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('plan')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Members')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
        // Read-only: no row, bulk, or header actions in Phase 0.
    }
}
