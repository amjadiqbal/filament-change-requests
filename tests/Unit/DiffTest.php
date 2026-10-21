<?php

declare(strict_types=1);

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use AmjadIqbal\FilamentChangeRequests\ChangeRequestsPlugin;
use AmjadIqbal\FilamentChangeRequests\Tests\Fixtures\Product;
use Cjmellor\Approval\Models\Approval;

function approvalWith(array $new, array $old, bool $creation = false): Approval
{
    return new Approval([
        'approvalable_type' => (new Product)->getMorphClass(),
        'approvalable_id' => $creation ? null : 1,
        'new_data' => $new,
        'original_data' => $old,
    ]);
}

it('builds one row per changed attribute with labels and values', function (): void {
    $rows = ChangeRequests::diff(approvalWith(['price' => 95, 'is_active' => false], ['price' => 120, 'is_active' => true]));

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray(['attribute' => 'price', 'label' => 'Price', 'old' => '120', 'new' => '95', 'changed' => true])
        ->and($rows[1])->toMatchArray(['label' => 'Is Active', 'old' => 'Yes', 'new' => 'No']);
});

it('masks sensitive attributes', function (): void {
    $rows = ChangeRequests::diff(approvalWith(['password' => 'new-secret'], ['password' => 'old-secret']));

    expect($rows[0])->toMatchArray(['old' => ChangeRequests::MASK, 'new' => ChangeRequests::MASK, 'masked' => true]);
});

it('formats empty, array and unchanged values', function (): void {
    $rows = collect(ChangeRequests::diff(approvalWith(
        ['tags' => ['a', 'b'], 'stock' => 5, 'name' => null],
        ['tags' => ['a'], 'stock' => '5', 'name' => 'Mug'],
    )))->keyBy('attribute');

    expect($rows['tags']['new'])->toContain('"b"')
        ->and($rows['stock']['changed'])->toBeFalse()
        ->and($rows['name']['new'])->toBe('—');
});

it('leaves the before column blank for a new record', function (): void {
    $rows = ChangeRequests::diff(approvalWith(['name' => 'Water bottle'], [], creation: true));

    expect($rows[0])->toMatchArray(['old' => '', 'new' => 'Water bottle']);
});

it('uses custom attribute labels from the plugin', function (): void {
    ChangeRequestsPlugin::get()->attributeLabels(['sku' => 'SKU']);

    expect(ChangeRequests::attributeLabel('sku'))->toBe('SKU');
});

it('labels statuses including custom states', function (): void {
    config()->set('approval.states.needs_info', ['name' => 'Needs info']);

    expect(ChangeRequests::statusLabel('pending'))->toBe('Pending')
        ->and(ChangeRequests::statusLabel('needs_info'))->toBe('Needs info')
        ->and(ChangeRequests::statusOptions())->toHaveKey('needs_info');
});
