<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tests\Fixtures;

use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
