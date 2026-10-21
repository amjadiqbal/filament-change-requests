<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tests\Fixtures;

use AmjadIqbal\FilamentChangeRequests\Tables\Columns\PendingChangesColumn;
use AmjadIqbal\FilamentChangeRequests\Tables\Filters\PendingChangesFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('price')->numeric()->required(),
            TextInput::make('stock')->numeric()->required(),
            Toggle::make('is_active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('price'),
                PendingChangesColumn::make(),
            ])
            ->filters([
                PendingChangesFilter::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
