<?php

declare(strict_types=1);

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use AmjadIqbal\FilamentChangeRequests\ChangeRequestsPlugin;
use AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource;
use AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource\Pages\ListChangeRequests;
use AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource\Pages\ViewChangeRequest;
use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\Product;
use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\ProductPolicy;
use AmjadIqbal\FilamentChangeRequests\Widgets\PendingChangeRequestsWidget;
use Cjmellor\Approval\Enums\ApprovalStatus;
use Cjmellor\Approval\Models\Approval;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->editor = $this->makeUser('Editor');
    $this->reviewer = $this->makeUser('Reviewer');

    $this->product = $this->makeProduct(['name' => 'Travel mug', 'price' => 120, 'stock' => 40]);

    $this->actingAs($this->editor);
    $this->product->update(['price' => 95, 'stock' => 55]);
    $this->approval = Approval::query()->sole();

    $this->actingAs($this->reviewer);
});

it('lists pending change requests by default', function (): void {
    $rejected = Approval::query()->create([
        'approvalable_type' => $this->product->getMorphClass(),
        'approvalable_id' => $this->product->getKey(),
        'state' => ApprovalStatus::Rejected,
        'new_data' => ['name' => 'Old idea'],
        'original_data' => ['name' => 'Travel mug'],
    ]);

    livewire(ListChangeRequests::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->approval])
        ->assertCanNotSeeTableRecords([$rejected])
        ->assertTableColumnStateSet('record', 'Travel mug', $this->approval)
        ->assertTableColumnStateSet('requestor', 'Editor', $this->approval)
        ->filterTable('status', 'rejected')
        ->assertCanSeeTableRecords([$rejected]);
});

it('filters to one record from the URL filter', function (): void {
    $other = $this->makeProduct(['name' => 'Plate', 'price' => 10, 'stock' => 1]);
    $this->actingAs($this->editor);
    $other->update(['price' => 12]);
    $this->actingAs($this->reviewer);
    $otherApproval = Approval::query()->latest('id')->first();

    livewire(ListChangeRequests::class)
        ->filterTable('record', ['approvalable_type' => $this->product->getMorphClass(), 'approvalable_id' => (string) $this->product->getKey()])
        ->assertCanSeeTableRecords([$this->approval])
        ->assertCanNotSeeTableRecords([$otherApproval]);
});

it('approves a change request and saves the change', function (): void {
    livewire(ListChangeRequests::class)
        ->callTableAction('approveChangeRequest', $this->approval)
        ->assertNotified('Changes approved and saved');

    expect($this->product->fresh())->price->toBe(95)->stock->toBe(55)
        ->and($this->approval->fresh()->state)->toBe(ApprovalStatus::Approved)
        ->and((int) $this->approval->fresh()->audited_by)->toBe($this->reviewer->id);
});

it('rejects a change request and leaves the record alone', function (): void {
    livewire(ListChangeRequests::class)
        ->callTableAction('rejectChangeRequest', $this->approval)
        ->assertNotified('Changes rejected');

    expect($this->product->fresh())->price->toBe(120)
        ->and($this->approval->fresh()->state)->toBe(ApprovalStatus::Rejected);
});

it('stops people reviewing their own change requests', function (): void {
    $this->actingAs($this->editor);

    livewire(ListChangeRequests::class)
        ->assertTableActionHidden('approveChangeRequest', $this->approval)
        ->assertTableActionHidden('rejectChangeRequest', $this->approval);

    expect(ChangeRequests::canReview($this->approval, $this->editor))->toBeFalse()
        ->and(ChangeRequests::canReview($this->approval, $this->reviewer))->toBeTrue();
});

it('allows self review when the panel turns the guard off', function (): void {
    ChangeRequestsPlugin::get()->preventSelfReview(false);

    expect(ChangeRequests::canReview($this->approval, $this->editor))->toBeTrue();

    ChangeRequestsPlugin::get()->preventSelfReview();
});

it('uses an authorizeUsing callback when one is set', function (): void {
    ChangeRequestsPlugin::get()->authorizeUsing(fn (Approval $approval, $user): bool => $user->name === 'Boss');

    expect(ChangeRequests::canReview($this->approval, $this->reviewer))->toBeFalse()
        ->and(ChangeRequests::canReview($this->approval, $this->makeUser('Boss')))->toBeTrue();

    ChangeRequestsPlugin::get()->authorizeUsing(null);
});

it('uses the reviewChangeRequest gate when the app defines it', function (): void {
    Gate::define(ChangeRequests::ABILITY, fn ($user, Approval $approval): bool => false);

    expect(ChangeRequests::canReview($this->approval, $this->reviewer))->toBeFalse();
});

it('checks the model policy, and refuses an edit whose record was deleted', function (): void {
    Gate::policy(Product::class, ProductPolicy::class);

    expect(ChangeRequests::canReview($this->approval, $this->reviewer))->toBeTrue()
        ->and(ChangeRequests::canReview($this->approval, $this->makeUser('Intern')))->toBeFalse();

    $this->product->delete();

    expect(ChangeRequests::canReview($this->approval->fresh(), $this->reviewer))->toBeFalse();
});

it('shows the diff and approves from the view page', function (): void {
    livewire(ViewChangeRequest::class, ['record' => $this->approval->getKey()])
        ->assertOk()
        ->assertSee('Before')
        ->assertSee('120')
        ->assertSee('95')
        ->callAction('approveChangeRequest')
        ->assertActionHidden('approveChangeRequest')
        ->assertActionVisible('rollBackChangeRequest');

    expect($this->product->fresh()->price)->toBe(95);
});

it('rolls back an approved change', function (): void {
    ChangeRequests::approve($this->approval);

    livewire(ViewChangeRequest::class, ['record' => $this->approval->getKey()])
        ->callAction('rollBackChangeRequest')
        ->assertNotified('Changes rolled back')
        ->assertActionHidden('rollBackChangeRequest');

    expect($this->product->fresh())->price->toBe(120)->stock->toBe(40)
        ->and(ChangeRequests::status($this->approval->fresh()))->toBe('rolled_back');

    $price = collect(ChangeRequests::diff($this->approval->fresh()))->firstWhere('attribute', 'price');

    expect($price)->toMatchArray(['old' => '120', 'new' => '95']);
});

it('approves and rejects in bulk, skipping what it cannot review', function (): void {
    $this->actingAs($this->reviewer);
    $this->product->update(['name' => 'Reviewer edit']);
    $own = Approval::query()->latest('id')->first();

    livewire(ListChangeRequests::class)
        ->callTableBulkAction('approveChangeRequests', [$this->approval, $own])
        ->assertNotified();

    expect($this->approval->fresh()->state)->toBe(ApprovalStatus::Approved)
        ->and($own->fresh()->state)->toBe(ApprovalStatus::Pending);
});

it('links an approved creation to the new record', function (): void {
    $this->actingAs($this->editor);
    (new Product(['name' => 'Water bottle', 'price' => 30, 'stock' => 10]))->save();
    $creation = Approval::query()->whereNull('approvalable_id')->sole();
    $this->actingAs($this->reviewer);

    expect(ChangeRequests::recordTitle($creation))->toBe('New Product');

    livewire(ListChangeRequests::class)->callTableAction('approveChangeRequest', $creation);

    $created = Product::query()->where('name', 'Water bottle')->sole();

    expect((int) $creation->fresh()->approvalable_id)->toBe($created->id)
        ->and(ChangeRequests::recordTitle($creation->fresh()))->toBe('Water bottle');
});

it('shows a navigation badge with the pending count', function (): void {
    expect(ChangeRequestResource::getNavigationBadge())->toBe('1');

    ChangeRequests::approve($this->approval);

    expect(ChangeRequestResource::getNavigationBadge())->toBeNull();
});

it('renders the pending widget with actions', function (): void {
    livewire(PendingChangeRequestsWidget::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->approval])
        ->callTableAction('rejectChangeRequest', $this->approval);

    expect($this->approval->fresh()->state)->toBe(ApprovalStatus::Rejected);
});

it('registers the resource on the panel', function (): void {
    expect(Filament::getPanel('admin')->getResources())->toContain(ChangeRequestResource::class)
        ->and(ChangeRequestResource::getUrl('index'))->toEndWith('/admin/change-requests');
});
