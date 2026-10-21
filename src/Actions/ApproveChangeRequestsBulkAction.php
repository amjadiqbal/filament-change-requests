<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Actions;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Throwable;

class ApproveChangeRequestsBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'approveChangeRequests';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.actions.approve.bulk_label'));
        $this->color('success');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->requiresConfirmation();
        $this->modalDescription(__('filament-change-requests::change-requests.actions.approve.bulk_description'));
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

                try {
                    ChangeRequests::approve($record);
                    $done++;
                } catch (Throwable $exception) {
                    report($exception);
                    $skipped++;
                }
            }

            Notification::make()
                ->title(__('filament-change-requests::change-requests.actions.approve.bulk_done', ['count' => $done]))
                ->body($skipped ? __('filament-change-requests::change-requests.actions.bulk_skipped', ['count' => $skipped]) : null)
                ->color($skipped ? 'warning' : 'success')
                ->icon($skipped ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->send();
        });
    }
}
