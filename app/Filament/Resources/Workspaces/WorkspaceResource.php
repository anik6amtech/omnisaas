<?php

namespace App\Filament\Resources\Workspaces;

use App\Filament\Resources\Workspaces\Pages\ListWorkspaces;
use App\Filament\Resources\Workspaces\Tables\WorkspacesTable;
use App\Models\Workspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Placeholder control-plane resource: a READ-ONLY listing of all tenant
 * workspaces. Full tenant & subscription management (impersonation, comps,
 * limit overrides, …) lands in epic E9. Operators only — gated by the panel's
 * admin guard.
 */
class WorkspaceResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Tenants';

    public static function table(Table $table): Table
    {
        return WorkspacesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkspaces::route('/'),
        ];
    }

    // Read-only in Phase 0 — no create/edit/delete from the panel yet.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
