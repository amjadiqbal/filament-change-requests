<?php

declare(strict_types=1);

use AmjadIqbal\FilamentChangeRequests\ChangeRequests;

// Amjad's rule: no brown in any UI. Filament draws badges and modal icons as translucent fills in dark mode, and a
// translucent amber, orange or red fill on a dark background reads as brown. Warm colours stay text, lines and borders.

it('never gives a status badge a warm colour', function (string $status): void {
    expect(ChangeRequests::statusColor($status))->not->toBeIn(['warning', 'danger', 'orange', 'amber', 'red', 'yellow']);
})->with(['pending', 'approved', 'rejected', 'rolled_back', 'custom_state']);

it('uses no warm background fills in the stylesheet', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/dist/change-requests.css');

    expect($css)
        ->not->toMatch('/background[^;]*(warning|danger|orange|amber|red|yellow|brown|rgba)/i')
        ->not->toMatch('/(brown|tan|maroon|sienna|chocolate)\b/i');
});

it('keeps warm colours off badges and modal icons in the source', function (): void {
    $source = collect(glob(__DIR__.'/../../src/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE))
        ->map(fn (string $file): string => (string) file_get_contents($file))
        ->implode("\n");

    expect($source)
        ->not->toMatch("/color\\('warning'\\)/")
        ->not->toMatch("/getNavigationBadgeColor\\(\\)[^}]*'(warning|danger)'/s")
        ->not->toMatch("/modalIconColor\\('(warning|danger)'\\)/");
});
