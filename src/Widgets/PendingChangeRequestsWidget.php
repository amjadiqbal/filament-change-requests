<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Widgets;

use AmjadIqbal\FilamentChangeRequests\Actions\ApproveChangeRequestAction;
use AmjadIqbal\FilamentChangeRequests\Actions\RejectChangeRequestAction;
use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Dashboard widget: pending change requests, newest first, with approve and reject.
 */
class PendingChangeRequestsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        $resource = ChangeRequests::plugin()->getResource();

        return $table
            ->heading(__('filament-change-requests::change-requests.widget.heading'))
            ->query(fn () => ChangeRequests::wherePending(Approval::query())->with(['approvalable', 'creator'])->latest('id'))
            ->columns([
                TextColumn::make('record')
                    ->label(__('filament-change-requests::change-requests.columns.record'))
                    ->state(fn (Approval $record): string => ChangeRequests::recordTitle($record))
                    ->description(fn (Approval $record): string => ChangeRequests::modelLabel($record)),
                TextColumn::make('changes')
                    ->label(__('filament-change-requests::change-requests.columns.changes'))
                    ->state(fn (Approval $record): array => array_map(
                        fn (string $attribute): string => ChangeRequests::attributeLabel($attribute),
                        ChangeRequests::changedAttributes($record),
                    ))
                    ->badge()
                    ->color('gray')
                    ->limitList(3),
                TextColumn::make('requestor')
                    ->label(__('filament-change-requests::change-requests.columns.requested_by'))
                    ->state(fn (Approval $record): ?string => ChangeRequests::requestorName($record))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('filament-change-requests::change-requests.columns.requested_at'))
                    ->since(),
            ])
            ->recordActions([
                ApproveChangeRequestAction::make(),
                RejectChangeRequestAction::make(),
                Action::make('review')
                    ->label(__('filament-change-requests::change-requests.actions.review.label'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (Approval $record): string => $resource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('filament-change-requests::change-requests.widget.empty'))
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge)
            ->defaultPaginationPageOption(5);
    }
}
