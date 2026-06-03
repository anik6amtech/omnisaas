<?php

namespace App\Features;

use App\Models\Workspace;

/**
 * Example feature flag wired to the entitlements engine: comment-to-DM
 * automation (a Phase-2 capability) is available on higher plans. Resolve it
 * per workspace — `Feature::for($workspace)->active(CommentToDm::class)`.
 *
 * In E9 the operator drives this from DB-defined plan entitlements rather than
 * the hardcoded tier list below.
 */
class CommentToDm
{
    public function resolve(mixed $scope): bool
    {
        return $scope instanceof Workspace
            && in_array($scope->plan, ['business', 'agency'], true);
    }
}
