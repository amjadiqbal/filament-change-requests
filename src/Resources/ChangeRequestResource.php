<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Resources;

use AmjadIqbal\FilamentChangeRequests\Actions\ApproveChangeRequestAction;
use AmjadIqbal\FilamentChangeRequests\Actions\ApproveChangeRequestsBulkAction;
use AmjadIqbal\FilamentChangeRequests\Actions\RejectChangeRequestAction;
use AmjadIqbal\FilamentChangeRequests\Actions\RejectChangeRequestsBulkAction;
use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use AmjadIqbal\FilamentChangeRequests\Infolists\Components\ChangeDiffEntry;
use AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource\Pages\ListChangeRequests;
use AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource\Pages\ViewChangeRequest;
use BackedEnum;
use Cjmellor\Approval\Enums\ApprovalStatus;
use Cjmellor\Approval\Models\Approval;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ChangeRequestResource extends Resource
{
    protected static ?string $model = Approval::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $slug = 'change-requests';

    public static function getModelLabel(): string
    {
        return __('filament-change-requests::change-requests.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-change-requests::change-requests.resource.plural_model_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return ChangeRequests::plugin()->getNavigationGroup() ?? parent::getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return ChangeRequests::plugin()->getNavigationSort() ?? parent::getNavigationSort();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! ChangeRequests::plugin()->hasNavigationBadge()) {
            return null;
        }

        $count = ChangeRequests::wherePending(Approval::query())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function getRecordTitle(?Model $record): string
    {
        return __('filament-change-requests::change-requests.resource.record_title', ['id' => $record?->getKey()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['approvalable', 'creator']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('filament-change-requests::change-requests.view.summary'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('record')
                        ->label(__('filament-change-requests::change-requests.columns.record'))
                        ->state(fn (Approval $record): string => ChangeRequests::recordTitle($record))
                        ->url(fn (Approval $record): ?string => ChangeRequests::recordUrl($record))
                        ->color(fn (Approval $record): ?string => ChangeRequests::recordUrl($record) ? 'primary' : null),
                    TextEntry::make('type')
                        ->label(__('filament-change-requests::change-requests.columns.type'))
                        ->state(fn (Approval $record): string => ChangeRequests::isCreation($record)
                            ? __('filament-change-requests::change-requests.type.create')
                            : __('filament-change-requests::change-requests.type.update')),
                    TextEntry::make('status')
                        ->label(__('filament-change-requests::change-requests.columns.status'))
                        ->badge()
                        ->state(fn (Approval $record): string => ChangeRequests::status($record))
                        ->formatStateUsing(fn (string $state): string => ChangeRequests::statusLabel($state))
                        ->color(fn (string $state): string => ChangeRequests::statusColor($state))
                        ->icon(fn (string $state): Heroicon => ChangeRequests::statusIcon($state)),
                    TextEntry::make('requestor')
                        ->label(__('filament-change-requests::change-requests.columns.requested_by'))
                        ->state(fn (Approval $record): ?string => ChangeRequests::requestorName($record))
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label(__('filament-change-requests::change-requests.columns.requested_at'))
                        ->dateTime(),
                    TextEntry::make('reviewer')
                        ->label(__('filament-change-requests::change-requests.columns.reviewed_by'))
                        ->state(fn (Approval $record): ?string => ChangeRequests::reviewerName($record))
                        ->placeholder('—'),
                    TextEntry::make('rolled_back_at')
                        ->label(__('filament-change-requests::change-requests.columns.rolled_back_at'))
                        ->dateTime()
                        ->visible(fn (Approval $record): bool => $record->rolled_back_at !== null),
                ]),
            Section::make(__('filament-change-requests::change-requests.diff.label'))
                ->columnSpanFull()
                ->schema([
                    ChangeDiffEntry::make('diff')->hiddenLabel(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('record')
                    ->label(__('filament-change-requests::change-requests.columns.record'))
                    ->state(fn (Approval $record): string => ChangeRequests::recordTitle($record))
                    ->description(fn (Approval $record): string => ChangeRequests::modelLabel($record))
                    ->url(fn (Approval $record): ?string => ChangeRequests::recordUrl($record)),
                TextColumn::make('changes')
                    ->label(__('filament-change-requests::change-requests.columns.changes'))
                    ->state(fn (Approval $record): array => array_map(
                        fn (string $attribute): string => ChangeRequests::attributeLabel($attribute),
                        ChangeRequests::changedAttributes($record),
                    ))
                    ->badge()
                    ->color('gray')
                    ->limitList(3)
                    ->expandableLimitedList(),
                TextColumn::make('requestor')
                    ->label(__('filament-change-requests::change-requests.columns.requested_by'))
                    ->state(fn (Approval $record): ?string => ChangeRequests::requestorName($record))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('filament-change-requests::change-requests.columns.status'))
                    ->badge()
                    ->state(fn (Approval $record): string => ChangeRequests::status($record))
                    ->formatStateUsing(fn (string $state): string => ChangeRequests::statusLabel($state))
                    ->color(fn (string $state): string => ChangeRequests::statusColor($state))
                    ->icon(fn (string $state): Heroicon => ChangeRequests::statusIcon($state)),
                TextColumn::make('created_at')
                    ->label(__('filament-change-requests::change-requests.columns.requested_at'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('reviewer')
                    ->label(__('filament-change-requests::change-requests.columns.reviewed_by'))
                    ->state(fn (Approval $record): ?string => ChangeRequests::reviewerName($record))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament-change-requests::change-requests.columns.status'))
                    ->options(fn (): array => [
                        ...ChangeRequests::statusOptions(),
                        'rolled_back' => ChangeRequests::statusLabel('rolled_back'),
                    ])
                    ->default('pending')
                    ->query(function (Builder $query, array $data): Builder {
                        $state = $data['value'] ?? null;

                        return match ($state) {
                            null, '' => $query,
                            'rolled_back' => $query->where('state', ApprovalStatus::Approved)->whereNotNull('rolled_back_at'),
                            'approved' => $query->where('state', ApprovalStatus::Approved)->whereNull('custom_state')->whereNull('rolled_back_at'),
                            default => $query->whereState($state),
                        };
                    }),
                SelectFilter::make('model')
                    ->label(__('filament-change-requests::change-requests.columns.type_of_record'))
                    ->options(fn (): array => Approval::query()
                        ->distinct()
                        ->pluck('approvalable_type')
                        ->filter()
                        ->mapWithKeys(fn (string $type): array => [
                            $type => ChangeRequests::modelLabel(new Approval(['approvalable_type' => $type])),
                        ])
                        ->all())
                    ->attribute('approvalable_type'),
                Filter::make('record')
                    ->schema([
                        Hidden::make('approvalable_type'),
                        Hidden::make('approvalable_id'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['approvalable_type'] ?? null) && filled($data['approvalable_id'] ?? null),
                        fn (Builder $query): Builder => $query
                            ->where('approvalable_type', $data['approvalable_type'])
                            ->where('approvalable_id', $data['approvalable_id']),
                    ))
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['approvalable_type'] ?? null) || blank($data['approvalable_id'] ?? null)) {
                            return null;
                        }

                        $approval = new Approval([
                            'approvalable_type' => $data['approvalable_type'],
                            'approvalable_id' => $data['approvalable_id'],
                        ]);

                        return __('filament-change-requests::change-requests.filters.record', [
                            'record' => ChangeRequests::recordTitle($approval),
                        ]);
                    }),
            ])
            ->recordActions([
                ApproveChangeRequestAction::make(),
                RejectChangeRequestAction::make(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ApproveChangeRequestsBulkAction::make(),
                    RejectChangeRequestsBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('filament-change-requests::change-requests.resource.empty_heading'))
            ->emptyStateDescription(__('filament-change-requests::change-requests.resource.empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentCheck);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangeRequests::route('/'),
            'view' => ViewChangeRequest::route('/{record}'),
        ];
    }
}
