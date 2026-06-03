<?php

use App\Domain\Catalog\Actions\ImportCatalogCsv;
use App\Domain\Catalog\Services\ProductCatalog;
use App\Domain\Knowledge\Actions\IngestKnowledge;
use App\Domain\Knowledge\Jobs\IndexKnowledge;
use App\Domain\Tenancy\Context\CurrentWorkspace;
use App\Livewire\Catalog\CatalogPage;
use App\Livewire\Knowledge\KnowledgePage;
use App\Models\KnowledgeDocument;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function inWorkspace(): Workspace
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($user, ['role' => 'owner']);
    test()->actingAs($user, 'web');
    app(CurrentWorkspace::class)->set($workspace);

    return $workspace;
}

it('imports products from CSV, upserting on sku', function () {
    inWorkspace();
    $csv = "name,sku,price,stock,description\nT-Shirt,TS1,500,10,Cotton tee\nMug,MG1,300,5,Ceramic\n";

    $count = app(ImportCatalogCsv::class)->execute($csv);
    expect($count)->toBe(2)->and(Product::withoutGlobalScopes()->count())->toBe(2);

    // Re-import with updated price -> upsert, not duplicate.
    app(ImportCatalogCsv::class)->execute("name,sku,price,stock\nT-Shirt,TS1,650,3\n");
    expect(Product::withoutGlobalScopes()->count())->toBe(2)
        ->and((float) Product::withoutGlobalScopes()->where('sku', 'TS1')->value('price'))->toBe(650.0);
});

it('looks up catalog facts relationally for AI grounding', function () {
    $ws = inWorkspace();
    Product::factory()->recycle($ws)->create(['name' => 'Blue Jacket', 'price' => 1200, 'stock' => 4]);
    Product::factory()->recycle($ws)->outOfStock()->create(['name' => 'Red Cap']);

    $facts = app(ProductCatalog::class)->factsFor('jacket');

    expect($facts)->toContain('Blue Jacket')->toContain('1200')->toContain('in stock')
        ->not->toContain('Red Cap');
});

it('ingests a knowledge document and queues indexing', function () {
    Queue::fake([IndexKnowledge::class]);
    inWorkspace();

    $doc = app(IngestKnowledge::class)->execute('Delivery inside Dhaka is 60 taka.', 'policy', 'Delivery');

    expect($doc->status)->toBe('pending')->and($doc->source_type)->toBe('policy');
    Queue::assertPushed(IndexKnowledge::class, fn (IndexKnowledge $job) => $job->document->is($doc));
});

it('imports a CSV through the Catalog Livewire page', function () {
    inWorkspace();
    $file = UploadedFile::fake()->createWithContent('products.csv', "name,sku,price,stock\nSandal,SD1,800,7\n");

    Livewire::test(CatalogPage::class)
        ->set('csv', $file)
        ->call('import')
        ->assertSet('imported', 1);

    expect(Product::withoutGlobalScopes()->where('sku', 'SD1')->exists())->toBeTrue();
});

it('adds a knowledge document through the Knowledge Livewire page', function () {
    Queue::fake([IndexKnowledge::class]);
    inWorkspace();

    Livewire::test(KnowledgePage::class)
        ->set('content', 'We accept bKash and Nagad.')
        ->set('title', 'Payments')
        ->call('ingest')
        ->assertHasNoErrors()
        ->assertSet('content', '');

    expect(KnowledgeDocument::withoutGlobalScopes()->where('title', 'Payments')->exists())->toBeTrue();
    Queue::assertPushed(IndexKnowledge::class);
});
