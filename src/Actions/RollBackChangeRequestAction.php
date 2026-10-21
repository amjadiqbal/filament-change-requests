<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Actions;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Throwable;

class RollBackChangeRequestAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'rollBackChangeRequest';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.actions.roll_back.label'));
        $this->color('gray');
        $this->icon(Heroicon::OutlinedArrowUturnLeft);
        $this->requiresConfirmation();
        $this->modalHeading(fn (Approval $record): string => __('filament-change-requests::change-requests.actions.roll_back.heading', [
            'record' => ChangeRequests::recordTitle($record),
        ]));
        $this->modalDescription(__('filament-change-requests::change-requests.actions.roll_back.description'));
        $this->modalSubmitActionLabel(__('filament-change-requests::change-requests.actions.roll_back.label'));
        $this->visible(fn (Approval $record): bool => ChangeRequests::canRollBack($record) && ChangeRequests::canReview($record));

        $this->action(function (Approval $record): void {
            if (! ChangeRequests::canRollBack($record) || ! ChangeRequests::canReview($record)) {
                $this->failure();

                return;
            }

            try {
                $record->rollback();
            } catch (Throwable $exception) {
                report($exception);

                Notification::make()
                    ->danger()
                    ->title(__('filament-change-requests::change-requests.actions.roll_back.failed'))
                    ->body($exception->getMessage())
                    ->send();

                $this->halt();
            }

            $this->success();
        });

        $this->successNotificationTitle(__('filament-change-requests::change-requests.actions.roll_back.done'));
        $this->failureNotificationTitle(__('filament-change-requests::change-requests.actions.not_allowed'));
    }
}
