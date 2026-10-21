<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Infolists\Components;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Infolists\Components\Entry;
use Filament\Support\Components\Contracts\HasEmbeddedView;

/**
 * A field-by-field table of a change request: attribute, value before, value after.
 */
class ChangeDiffEntry extends Entry implements HasEmbeddedView
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.diff.label'));
        $this->state(fn (?Approval $record): array => $record ? ChangeRequests::diff($record) : []);
        $this->placeholder(__('filament-change-requests::change-requests.diff.empty'));
        $this->columnSpanFull();
    }

    public function toEmbeddedHtml(): string
    {
        /** @var array<int, array{attribute: string, label: string, old: string, new: string, changed: bool, masked: bool}> $rows */
        $rows = $this->getState() ?? [];

        $attributes = $this->getExtraAttributeBag()->class(['fi-in-key-value', 'fi-cr-diff']);

        ob_start(); ?>

        <table <?= $attributes->toHtml() ?>>
            <thead>
                <tr>
                    <th scope="col"><?= e(__('filament-change-requests::change-requests.diff.field')) ?></th>
                    <th scope="col"><?= e(__('filament-change-requests::change-requests.diff.before')) ?></th>
                    <th scope="col"><?= e(__('filament-change-requests::change-requests.diff.after')) ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $row) { ?>
                    <tr class="<?= $row['changed'] ? 'fi-cr-diff-changed' : 'fi-cr-diff-same' ?>" data-attribute="<?= e($row['attribute']) ?>">
                        <th scope="row"><?= e($row['label']) ?></th>
                        <td><span class="fi-cr-diff-old"><?= e($row['old']) ?></span></td>
                        <td><span class="fi-cr-diff-new"><?= e($row['new']) ?></span></td>
                    </tr>
                <?php } ?>

                <?php if (empty($rows)) { ?>
                    <tr>
                        <td colspan="3" class="fi-in-placeholder"><?= e($this->getPlaceholder()) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php return $this->wrapEmbeddedHtml((string) ob_get_clean());
    }
}
