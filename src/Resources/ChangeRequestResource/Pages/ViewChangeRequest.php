<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource\Pages;

use AmjadIqbal\FilamentChangeRequests\Actions\ApproveChangeRequestAction;
use AmjadIqbal\FilamentChangeRequests\Actions\RejectChangeRequestAction;
use AmjadIqbal\FilamentChangeRequests\Actions\RollBackChangeRequestAction;
use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewChangeRequest extends ViewRecord
{
    public static function getResource(): string
    {
        return ChangeRequests::plugin()->getResource();
    }

    protected function getHeaderActions(): array
    {
        return [
            ApproveChangeRequestAction::make()->after(fn () => $this->refreshRecord()),
            RejectChangeRequestAction::make()->after(fn () => $this->refreshRecord()),
            RollBackChangeRequestAction::make()->after(fn () => $this->refreshRecord()),
            Action::make('openRecord')
                ->label(__('filament-change-requests::change-requests.actions.open_record.label'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Approval $record): ?string => ChangeRequests::recordUrl($record))
                ->visible(fn (Approval $record): bool => ChangeRequests::recordUrl($record) !== null),
        ];
    }

    protected function refreshRecord(): void
    {
        $this->record->refresh();
        $this->record->unsetRelation('approvalable');
    }
}
