<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tests\Fixtures;

use AmjadIqbal\FilamentChangeRequests\Actions\ViewChangeRequestsAction;
use AmjadIqbal\FilamentChangeRequests\Pages\Concerns\SubmitsChangeRequests;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use SubmitsChangeRequests;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewChangeRequestsAction::make()];
    }
}
