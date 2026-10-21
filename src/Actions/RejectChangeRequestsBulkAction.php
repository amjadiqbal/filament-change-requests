<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Actions;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class RejectChangeRequestsBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'rejectChangeRequests';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.actions.reject.bulk_label'));
        $this->color('danger');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->requiresConfirmation();
        $this->modalIconColor('gray');
        $this->modalSubmitAction(fn (Action $action): Action => $action->outlined());
        $this->modalDescription(__('filament-change-requests::change-requests.actions.reject.bulk_description'));
        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records): void {
            $done = 0;
            $skipped = 0;

            /** @var Approval $record */
            foreach ($records as $record) {
                if (! ChangeRequests::isPending($record) || ! ChangeRequests::canReview($record)) {
                    $skipped++;

                    continue;
                }

                $record->reject();
                $done++;
            }

            Notification::make()
                ->title(__('filament-change-requests::change-requests.actions.reject.bulk_done', ['count' => $done]))
                ->body($skipped ? __('filament-change-requests::change-requests.actions.bulk_skipped', ['count' => $skipped]) : null)
                ->color($skipped ? 'warning' : 'success')
                ->icon($skipped ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->send();
        });
    }
}
