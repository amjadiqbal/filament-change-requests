<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/amjadiqbal/filament-change-requests/main/art/banner-dark.png">
    <img alt="Change Requests — review record edits before they are saved" src="https://raw.githubusercontent.com/amjadiqbal/filament-change-requests/main/art/banner-light.png" width="100%">
  </picture>
</p>

# Change Requests for Filament

Hold edits to your Filament records as change requests. An approver sees the old and new value of each field, then
approves, rejects or rolls back the change. It works with Filament 4 and 5 and is built on
[cjmellor/approval](https://github.com/cjmellor/approval).

[![Latest Version](https://img.shields.io/packagist/v/amjadiqbal/filament-change-requests.svg?style=flat-square)](https://packagist.org/packages/amjadiqbal/filament-change-requests)
[![PHP Version](https://img.shields.io/packagist/php-v/amjadiqbal/filament-change-requests?style=flat-square)](https://packagist.org/packages/amjadiqbal/filament-change-requests)
[![License](https://img.shields.io/packagist/l/amjadiqbal/filament-change-requests.svg?style=flat-square)](LICENSE.md)

![A change request with its field-by-field diff](https://raw.githubusercontent.com/amjadiqbal/filament-change-requests/main/art/screenshots/view-diff.png)

## What it does

- **Edits wait for approval.** When someone saves a record of a model that needs approval, nothing changes yet.
  - They see "Changes submitted for approval" instead of "Saved".
  - The form goes back to the saved values.
  - A "Pending changes" button links to the request.
- **New records wait too.** A held create says "Submitted for approval" and goes back to the list. No broken
  redirect to a record that doesn't exist yet.
- **A field-by-field diff.** Each changed field shows its value before and after. Passwords and tokens are masked.
- **Approve, reject, roll back.**
  - You can approve or reject from the list, one at a time or in bulk, from the request page, or from a dashboard widget.
  - Rolling back an approved change restores the previous values.
- **Four-eyes by default.** Nobody can approve or reject their own change request.
- **On your existing tables:** a pending-changes count column and a "Has pending changes" filter.
- Light and dark mode. English strings, all translatable.

## Requirements

- PHP 8.3+
- Laravel 12.4+ or 13
- Filament 4 or 5
- [cjmellor/approval](https://github.com/cjmellor/approval) 2.1+, installed for you

## Installation

```bash
composer require amjadiqbal/filament-change-requests
```

`cjmellor/approval` is installed with it. Run its migrations, then publish the plugin's stylesheet:

```bash
php artisan migrate
php artisan filament:assets
```

Register the plugin on your panel:

```php
use AmjadIqbal\FilamentChangeRequests\ChangeRequestsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(ChangeRequestsPlugin::make());
}
```

## Usage

### 1. Make a model need approval

Add cjmellor/approval's trait to the model:

```php
use Cjmellor\Approval\Concerns\MustBeApproved;

class Product extends Model
{
    use MustBeApproved;

    // Optional: only these attributes need approval; others save straight away.
    protected array $approvalAttributes = ['price', 'stock'];
}
```

### 2. Use the page traits on the model's resource

Without these, Filament would say "Saved" or "Created" for a change that is only waiting for approval.

```php
use AmjadIqbal\FilamentChangeRequests\Actions\ViewChangeRequestsAction;
use AmjadIqbal\FilamentChangeRequests\Pages\Concerns\SubmitsChangeRequests;

class EditProduct extends EditRecord
{
    use SubmitsChangeRequests;

    protected function getHeaderActions(): array
    {
        return [
            ViewChangeRequestsAction::make(), // "Pending changes (2)", only when there are any
        ];
    }
}
```

```php
use AmjadIqbal\FilamentChangeRequests\Pages\Concerns\SubmitsCreationRequests;

class CreateProduct extends CreateRecord
{
    use SubmitsCreationRequests;
}
```

### 3. Optional: show pending changes on the resource table

```php
use AmjadIqbal\FilamentChangeRequests\Tables\Columns\PendingChangesColumn;
use AmjadIqbal\FilamentChangeRequests\Tables\Filters\PendingChangesFilter;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
            PendingChangesColumn::make(),
        ])
        ->filters([
            PendingChangesFilter::make(),
        ]);
}
```

### 4. Optional: the dashboard widget

```php
use AmjadIqbal\FilamentChangeRequests\Widgets\PendingChangeRequestsWidget;

$panel->widgets([PendingChangeRequestsWidget::class]);
```

![The change requests list](https://raw.githubusercontent.com/amjadiqbal/filament-change-requests/main/art/screenshots/list.png)

## Who can approve

The plugin checks these in order:

1. **Self-review is blocked.** Nobody can approve or reject their own request. Turn this off with
   `->preventSelfReview(false)`.
2. **Your callback**, if you set one:
   ```php
   ChangeRequestsPlugin::make()
       ->authorizeUsing(fn (Approval $approval, User $user): bool => $user->is_admin)
   ```
3. **A `reviewChangeRequest` gate**, if your app defines one:
   ```php
   Gate::define('reviewChangeRequest', fn (User $user, Approval $approval): bool => $user->hasRole('editor-in-chief'));
   ```
4. **The model's policy.** A user who may `update` the record can review edits to it, and a user who may `create`
   one can review held creations.
5. **Anyone in the panel**, if the model has no policy.

## Configuration

```php
ChangeRequestsPlugin::make()
    ->navigationGroup('Catalog')
    ->navigationSort(3)
    ->navigationBadge(false)                          // hide the pending count
    ->attributeLabels(['sku' => 'SKU'])               // labels in the diff
    ->maskedAttributes(['iban'])                      // never show these values (password, tokens… are masked already)
    ->preventSelfReview()                             // default: on
    ->authorizeUsing(fn ($approval, $user) => true)
    ->resource(false);                                // or ->resource(MyChangeRequestResource::class)
```

The actions are plain Filament actions, so you can use them on your own pages or tables:
`ApproveChangeRequestAction`, `RejectChangeRequestAction`, `RollBackChangeRequestAction`,
`ApproveChangeRequestsBulkAction` and `RejectChangeRequestsBulkAction`.

## How it works

- `cjmellor/approval` stores each held change in an `approvals` table, with the new and the original values.
- Approving writes the new values to the record and records who approved it.
- For a held creation, the plugin links the change request to the record that approving it creates. cjmellor/approval
  alone leaves that link empty.
- Rolling back writes the original values back. The request keeps showing the change as it was asked for.
- Custom states from `config('approval.states')` appear in the status filter and badge.

## Testing

```bash
composer test       # Pest
composer analyse    # PHPStan level 5
composer lint       # Pint
scripts/ci-local.sh # PHP 8.3–8.5 × Laravel 12/13 × Filament 4/5
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Support

- 🐛 **Bug or feature request**: [open an issue](https://github.com/amjadiqbal/filament-change-requests/issues)
- 🔒 **Security issue**: please report it privately through GitHub's security tab

## Need this customised, or something built?

I'm available for custom development, package integration, and technical consulting.

[![Hire me on Upwork](https://img.shields.io/badge/Hire%20me-Upwork-6FDA44?style=flat-square&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/amjadkhatri)

## More packages

This is part of a family of open-source packages. See [all of them](https://github.com/amjadiqbal?tab=repositories).

| | |
|---|---|
| [filament-url-image-uploader](https://github.com/amjadiqbal/filament-url-image-uploader) | Upload images to Filament from a URL |
| [refresher](https://github.com/amjadiqbal/refresher) | Restore a cached test database instead of migrate:fresh |
| [kiln](https://github.com/amjadiqbal/kiln) | Deploy-time OPcache control for Laravel |

## Credits

- [Amjad Iqbal](https://github.com/amjadiqbal)
- Approval engine: [cjmellor/approval](https://github.com/cjmellor/approval) by Chris Mellor (MIT)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
