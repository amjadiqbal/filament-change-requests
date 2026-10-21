<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tests\Fixtures;

use AmjadIqbal\FilamentChangeRequests\ChangeRequestsPlugin;
use AmjadIqbal\FilamentChangeRequests\Widgets\PendingChangeRequestsWidget;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->resources([ProductResource::class])
            ->widgets([PendingChangeRequestsWidget::class])
            ->plugin(ChangeRequestsPlugin::make())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
            ]);
    }
}
