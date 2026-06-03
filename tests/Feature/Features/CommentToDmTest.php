<?php

use App\Features\CommentToDm;
use App\Models\Workspace;
use Laravel\Pennant\Feature;

it('gates comment-to-DM by the workspace plan (entitlements engine)', function () {
    $free = Workspace::factory()->create(['plan' => 'free']);
    $business = Workspace::factory()->create(['plan' => 'business']);

    expect(Feature::for($free)->active(CommentToDm::class))->toBeFalse()
        ->and(Feature::for($business)->active(CommentToDm::class))->toBeTrue();
});
