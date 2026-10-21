<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tests\Fixtures;

use AmjadIqbal\FilamentChangeRequests\Pages\Concerns\SubmitsCreationRequests;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use SubmitsCreationRequests;

    protected static string $resource = ProductResource::class;
}
