<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentChangeRequestsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-change-requests';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('change-requests', __DIR__.'/../resources/dist/change-requests.css'),
        ], 'amjadiqbal/filament-change-requests');
    }
}
