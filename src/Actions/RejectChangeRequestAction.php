<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Actions;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class RejectChangeRequestAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'rejectChangeRequest';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.actions.reject.label'));
        $this->color('danger');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->requiresConfirmation();
        $this->modalIconColor('gray');
        $this->modalSubmitAction(fn (Action $action): Action => $action->outlined());
        $this->modalHeading(fn (Approval $record): string => __('filament-change-requests::change-requests.actions.reject.heading', [
            'record' => ChangeRequests::recordTitle($record),
        ]));
        $this->modalDescription(__('filament-change-requests::change-requests.actions.reject.description'));
        $this->modalSubmitActionLabel(__('filament-change-requests::change-requests.actions.reject.label'));
        $this->visible(fn (Approval $record): bool => ChangeRequests::isPending($record) && ChangeRequests::canReview($record));

        $this->action(function (Approval $record): void {
            if (! ChangeRequests::isPending($record) || ! ChangeRequests::canReview($record)) {
                $this->failure();

                return;
            }

            $record->reject();

            $this->success();
        });

        $this->successNotificationTitle(__('filament-change-requests::change-requests.actions.reject.done'));
        $this->failureNotificationTitle(__('filament-change-requests::change-requests.actions.not_allowed'));
    }
}
