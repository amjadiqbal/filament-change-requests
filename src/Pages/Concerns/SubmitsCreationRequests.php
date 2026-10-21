<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Pages\Concerns;

use Cjmellor\Approval\Models\Approval;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

/**
 * Use on a CreateRecord page of a model that uses cjmellor/approval's MustBeApproved trait.
 *
 * cjmellor/approval holds new records too: nothing is inserted until someone approves it. Without this
 * trait Filament would report "Created" and redirect to a record that does not exist.
 */
trait SubmitsCreationRequests
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();
        $lastId = (int) Approval::query()->max('id');

        $record = parent::handleRecordCreation($data);

        if ($record->exists) {
            return $record;
        }

        $held = Approval::query()
            ->where('id', '>', $lastId)
            ->where('approvalable_type', (new $model)->getMorphClass())
            ->whereNull('approvalable_id')
            ->exists();

        if (! $held) {
            return $record;
        }

        Notification::make()
            ->info()
            ->title(__('filament-change-requests::change-requests.notifications.creation_submitted.title'))
            ->body(__('filament-change-requests::change-requests.notifications.creation_submitted.body'))
            ->send();

        $this->redirect(static::getResource()::getUrl('index'));

        // Halt without a rollback, so the change request is kept and nothing else runs.
        throw new Halt;
    }
}
