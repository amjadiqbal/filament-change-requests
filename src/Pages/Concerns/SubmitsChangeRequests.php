<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Pages\Concerns;

use Cjmellor\Approval\Models\Approval;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Use on an EditRecord page of a model that uses cjmellor/approval's MustBeApproved trait.
 *
 * When a save is held for approval, the form is reset to the saved values and the user sees
 * "Changes submitted for approval" instead of "Saved".
 */
trait SubmitsChangeRequests
{
    protected ?Approval $submittedChangeRequest = null;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $lastId = $this->latestChangeRequestId($record);

        $record = parent::handleRecordUpdate($record, $data);

        $this->submittedChangeRequest = Approval::query()
            ->where('approvalable_type', $record->getMorphClass())
            ->where('approvalable_id', $record->getKey())
            ->when($lastId, fn ($query) => $query->where('id', '>', $lastId))
            ->latest('id')
            ->first();

        if ($this->submittedChangeRequest) {
            $record->refresh();
            $this->fillForm();
        }

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        if (! $this->submittedChangeRequest) {
            return parent::getSavedNotification();
        }

        return Notification::make()
            ->info()
            ->title(__('filament-change-requests::change-requests.notifications.submitted.title'))
            ->body(__('filament-change-requests::change-requests.notifications.submitted.body'));
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->submittedChangeRequest ? null : parent::getRedirectUrl();
    }

    protected function latestChangeRequestId(Model $record): ?int
    {
        $id = Approval::query()
            ->where('approvalable_type', $record->getMorphClass())
            ->where('approvalable_id', $record->getKey())
            ->max('id');

        return $id === null ? null : (int) $id;
    }
}
