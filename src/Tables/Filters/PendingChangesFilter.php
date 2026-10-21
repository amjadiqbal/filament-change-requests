<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tables\Filters;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Has pending changes" toggle for a table of a model that uses MustBeApproved.
 */
class PendingChangesFilter extends Filter
{
    public static function make(?string $name = 'has_pending_changes'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.filters.has_pending_changes'));
        $this->toggle();
        $this->query(fn (Builder $query): Builder => $query->whereHas(
            'approvals',
            fn (Builder $approvals): Builder => ChangeRequests::wherePending($approvals),
        ));
    }
}
