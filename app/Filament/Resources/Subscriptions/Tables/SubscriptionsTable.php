<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Services\SubscriptionLifecycle;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workspace.name')->label('Tenant')->searchable()->sortable(),
                TextColumn::make('plan.name')->badge(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('current_period_ends_at')->dateTime()->sortable(),
                TextColumn::make('failed_charges')->label('Failed')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(SubscriptionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->name])->all(),
                ),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn (Subscription $record): bool => $record->status !== SubscriptionStatus::Cancelled)
                    ->action(fn (Subscription $record) => app(SubscriptionLifecycle::class)->cancel($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
