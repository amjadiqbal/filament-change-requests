<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Actions;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ApproveChangeRequestAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approveChangeRequest';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.actions.approve.label'));
        $this->color('success');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->requiresConfirmation();
        $this->modalHeading(fn (Approval $record): string => __('filament-change-requests::change-requests.actions.approve.heading', [
            'record' => ChangeRequests::recordTitle($record),
        ]));
        $this->modalDescription(__('filament-change-requests::change-requests.actions.approve.description'));
        $this->modalSubmitActionLabel(__('filament-change-requests::change-requests.actions.approve.label'));
        $this->visible(fn (Approval $record): bool => ChangeRequests::isPending($record) && ChangeRequests::canReview($record));

        $this->action(function (Approval $record): void {
            if (! ChangeRequests::isPending($record) || ! ChangeRequests::canReview($record)) {
                $this->failure();

                return;
            }

            try {
                ChangeRequests::approve($record);
            } catch (Throwable $exception) {
                report($exception);

                Notification::make()
                    ->danger()
                    ->title(__('filament-change-requests::change-requests.actions.approve.failed'))
                    ->body($exception->getMessage())
                    ->send();

                $this->halt();
            }

            $this->success();
        });

        $this->successNotificationTitle(__('filament-change-requests::change-requests.actions.approve.done'));
        $this->failureNotificationTitle(__('filament-change-requests::change-requests.actions.not_allowed'));
    }
}
