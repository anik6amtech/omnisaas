<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')->required(),
                        TextInput::make('price_bdt')->numeric()->prefix('৳')->default(0),
                        TextInput::make('price_usd')->numeric()->prefix('$')->default(0),
                        TextInput::make('billing_cycle')->datalist(['monthly', 'yearly'])->default('monthly')->required(),
                        TextInput::make('trial_days')->numeric()->default(0),
                        Toggle::make('active')->default(true),
                    ]),

                Section::make('Entitlements')
                    ->description('DB-defined limits & feature toggles read by the tenant plane at runtime — zero deploy.')
                    ->schema([
                        KeyValue::make('entitlements.limits')
                            ->label('Numeric limits (blank = unlimited)')
                            ->keyLabel('Limit')
                            ->valueLabel('Value')
                            ->default(['channels' => 1, 'ai_replies' => 200, 'seats' => 1, 'products' => 50]),
                        TagsInput::make('entitlements.features')
                            ->label('Feature toggles')
                            ->placeholder('e.g. comment_to_dm, instagram'),
                    ]),
            ]);
    }
}
