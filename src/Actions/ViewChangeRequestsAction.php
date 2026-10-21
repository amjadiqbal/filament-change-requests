<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Actions;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * For a record's Edit or View page: "Pending changes (2)", linking to that record's change requests.
 * Hidden when the record has no pending change requests.
 */
class ViewChangeRequestsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'viewChangeRequests';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Model $record): string => __('filament-change-requests::change-requests.actions.view_pending.label', [
            'count' => static::pendingCount($record),
        ]));
        $this->color('info');
        $this->icon(Heroicon::OutlinedClock);
        $this->visible(fn (Model $record): bool => static::pendingCount($record) > 0);
        $this->url(fn (Model $record): string => ChangeRequests::plugin()->getResource()::getUrl('index', [
            'filters' => [
                'record' => [
                    'approvalable_type' => $record->getMorphClass(),
                    'approvalable_id' => (string) $record->getKey(),
                ],
                'status' => ['value' => 'pending'],
            ],
        ]));
    }

    public static function pendingCount(Model $record): int
    {
        return ChangeRequests::wherePending(Approval::query())
            ->where('approvalable_type', $record->getMorphClass())
            ->where('approvalable_id', $record->getKey())
            ->count();
    }
}
