<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests;

use AmjadIqbal\FilamentChangeRequests\Resources\ChangeRequestResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Throwable;

class ChangeRequestsPlugin implements Plugin
{
    public const ID = 'change-requests';

    protected bool $hasResource = true;

    protected ?string $resource = null;

    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected bool $hasNavigationBadge = true;

    protected bool $preventsSelfReview = true;

    protected ?Closure $authorizeUsing = null;

    /** @var array<int, string> */
    protected array $maskedAttributes = [
        'password',
        'remember_token',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /** @var array<string, string> */
    protected array $attributeLabels = [];

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * The plugin registered on the current panel, or a default instance outside a panel.
     */
    public static function get(): static
    {
        try {
            $panel = Filament::getCurrentOrDefaultPanel();

            if ($panel?->hasPlugin(static::ID)) {
                /** @var static */
                return $panel->getPlugin(static::ID);
            }
        } catch (Throwable) {
            // No panels registered.
        }

        return app(static::class);
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void
    {
        if ($this->hasResource) {
            $panel->resources([$this->getResource()]);
        }
    }

    public function boot(Panel $panel): void {}

    public function resource(bool|string $condition = true): static
    {
        if (is_string($condition)) {
            $this->resource = $condition;
            $condition = true;
        }

        $this->hasResource = $condition;

        return $this;
    }

    /**
     * @return class-string<ChangeRequestResource>
     */
    public function getResource(): string
    {
        /** @var class-string<ChangeRequestResource> */
        return $this->resource ?? ChangeRequestResource::class;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function navigationBadge(bool $condition = true): static
    {
        $this->hasNavigationBadge = $condition;

        return $this;
    }

    public function hasNavigationBadge(): bool
    {
        return $this->hasNavigationBadge;
    }

    /**
     * When true (the default), nobody can approve or reject a change request they made themselves.
     */
    public function preventSelfReview(bool $condition = true): static
    {
        $this->preventsSelfReview = $condition;

        return $this;
    }

    public function preventsSelfReview(): bool
    {
        return $this->preventsSelfReview;
    }

    /**
     * Decide who may approve, reject or roll back a change request.
     * The closure receives `$approval` (the change request) and `$user`, and returns a bool.
     */
    public function authorizeUsing(?Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function getAuthorizeUsing(): ?Closure
    {
        return $this->authorizeUsing;
    }

    /**
     * Attributes whose values are never shown in a diff.
     *
     * @param  array<int, string>  $attributes
     */
    public function maskedAttributes(array $attributes, bool $merge = true): static
    {
        $this->maskedAttributes = $merge
            ? array_values(array_unique([...$this->maskedAttributes, ...$attributes]))
            : $attributes;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getMaskedAttributes(): array
    {
        return $this->maskedAttributes;
    }

    /**
     * Human labels for attributes in the diff, e.g. ['sku' => 'SKU'].
     *
     * @param  array<string, string>  $labels
     */
    public function attributeLabels(array $labels): static
    {
        $this->attributeLabels = [...$this->attributeLabels, ...$labels];

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getAttributeLabels(): array
    {
        return $this->attributeLabels;
    }
}
