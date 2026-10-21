<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource\Pages;

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;
use Filament\Resources\Pages\ListRecords;

class ListChangeRequests extends ListRecords
{
    public static function getResource(): string
    {
        return ChangeRequests::plugin()->getResource();
    }
}
