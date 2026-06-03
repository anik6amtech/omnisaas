<?php

namespace App\Filament\Resources\Workspaces\Pages;

use App\Filament\Resources\Workspaces\WorkspaceResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkspaces extends ListRecords
{
    protected static string $resource = WorkspaceResource::class;

    // Read-only listing — no create action.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
