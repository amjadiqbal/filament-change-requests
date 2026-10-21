<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tables\Columns;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Number of pending change requests per row, for a table of a model that uses MustBeApproved.
 * Counted in the table query (withCount), so there is no query per row.
 */
class PendingChangesColumn extends TextColumn
{
    public static function make(?string $name = 'approvals_count'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-change-requests::change-requests.columns.pending_changes'));
        $this->counts(['approvals' => fn (Builder $query): Builder => ChangeRequests::wherePending($query)]);
        $this->badge();
        $this->color(fn (?int $state): string => $state ? 'warning' : 'gray');
        $this->formatStateUsing(fn (?int $state): string => $state ? (string) $state : '—');
        $this->alignCenter();
        $this->sortable();
    }
}
