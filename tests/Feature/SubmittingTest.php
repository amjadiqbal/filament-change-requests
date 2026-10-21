<?php

declare(strict_types=1);

use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\CreateProduct;
use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\EditProduct;
use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\ListProducts;
use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\Product;
use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\ProductResource;
use Cjmellor\Approval\Enums\ApprovalStatus;
use Cjmellor\Approval\Models\Approval;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->editor = $this->makeUser('Editor');
    $this->actingAs($this->editor);

    $this->product = $this->makeProduct([
        'name' => 'Travel mug',
        'price' => 120,
        'stock' => 40,
    ]);
});

it('holds an edit as a change request instead of saving it', function (): void {
    livewire(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->fillForm(['price' => 95, 'stock' => 55])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Changes submitted for approval')
        ->assertSchemaStateSet(['price' => 120, 'stock' => 40]);

    expect($this->product->fresh())
        ->price->toBe(120)
        ->stock->toBe(40);

    $approval = Approval::query()->sole();

    expect($approval->state)->toBe(ApprovalStatus::Pending)
        ->and($approval->new_data->getArrayCopy())->toMatchArray(['price' => 95, 'stock' => 55])
        ->and($approval->original_data->getArrayCopy())->toMatchArray(['price' => 120, 'stock' => 40])
        ->and((int) $approval->creator_id)->toBe($this->editor->id);
});

it('still saves normally when nothing needs approval', function (): void {
    livewire(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->call('save')
        ->assertNotified('Saved');

    expect(Approval::query()->count())->toBe(0);
});

it('shows the pending changes action on the edit page', function (): void {
    livewire(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionHidden('viewChangeRequests')
        ->fillForm(['price' => 95])
        ->call('save');

    livewire(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionVisible('viewChangeRequests');
});

it('holds a new record as a change request', function (): void {
    livewire(CreateProduct::class)
        ->fillForm(['name' => 'Water bottle', 'price' => 30, 'stock' => 10])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Submitted for approval')
        ->assertRedirect(ProductResource::getUrl('index'));

    expect(Product::query()->where('name', 'Water bottle')->exists())->toBeFalse();

    $approval = Approval::query()->sole();

    expect($approval->approvalable_id)->toBeNull()
        ->and($approval->new_data['name'])->toBe('Water bottle');
});

it('counts and filters pending changes on the record table', function (): void {
    $other = $this->makeProduct(['name' => 'Plate', 'price' => 10, 'stock' => 1]);

    $this->product->update(['price' => 99]);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$this->product, $other])
        ->assertTableColumnStateSet('approvals_count', 1, $this->product)
        ->filterTable('has_pending_changes')
        ->assertCanSeeTableRecords([$this->product])
        ->assertCanNotSeeTableRecords([$other]);
});
