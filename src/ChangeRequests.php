<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests;

use BackedEnum;
use Cjmellor\Approval\Enums\ApprovalStatus;
use Cjmellor\Approval\Models\Approval;
use DateTimeInterface;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasName;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class ChangeRequests
{
    /** Gate ability checked when an app defines it and no `authorizeUsing()` callback is set. */
    public const ABILITY = 'reviewChangeRequest';

    public const MASK = '••••••';

    protected static bool $capturingCreation = false;

    protected static ?Model $capturedCreation = null;

    /** @var array<class-string<Model>, true> */
    protected static array $creationListeners = [];

    public static function plugin(): ChangeRequestsPlugin
    {
        return ChangeRequestsPlugin::get();
    }

    /**
     * Approve a change request. For a held creation, cjmellor/approval creates the record but leaves the
     * change request unlinked, so the new record's key is written back to it here.
     */
    public static function approve(Approval $approval): void
    {
        $class = static::isCreation($approval) ? static::modelClass($approval) : null;

        if (! $class) {
            $approval->approve();

            return;
        }

        if (! isset(static::$creationListeners[$class])) {
            static::$creationListeners[$class] = true;

            Event::listen("eloquent.created: {$class}", function (Model $model): void {
                if (static::$capturingCreation && static::$capturedCreation === null) {
                    static::$capturedCreation = $model;
                }
            });
        }

        static::$capturingCreation = true;
        static::$capturedCreation = null;

        try {
            $approval->approve();
        } finally {
            static::$capturingCreation = false;
        }

        if ($created = static::pullCapturedCreation()) {
            $approval->forceFill(['approvalable_id' => $created->getKey()])->saveQuietly();
        }
    }

    protected static function pullCapturedCreation(): ?Model
    {
        $model = static::$capturedCreation;
        static::$capturedCreation = null;

        return $model;
    }

    /**
     * Limit a change-request query (or relation) to pending requests, as cjmellor/approval's `pending()` does.
     *
     * @template TBuilder of Builder<Approval>|Builder<Model>
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public static function wherePending(Builder $query): Builder
    {
        return $query
            ->where('state', ApprovalStatus::Pending->value)
            ->whereNull('custom_state');
    }

    /**
     * Whether the user may approve, reject or roll back this change request.
     */
    public static function canReview(Approval $approval, ?Authenticatable $user = null): bool
    {
        $user ??= Filament::auth()->user() ?? auth()->user();

        if (! $user) {
            return false;
        }

        $plugin = static::plugin();

        if ($plugin->preventsSelfReview() && static::isRequestedBy($approval, $user)) {
            return false;
        }

        if ($callback = $plugin->getAuthorizeUsing()) {
            return (bool) $callback($approval, $user);
        }

        if (Gate::has(static::ABILITY)) {
            return Gate::forUser($user)->allows(static::ABILITY, $approval);
        }

        $class = static::modelClass($approval);

        if (! $class || ! Gate::getPolicyFor($class)) {
            return true;
        }

        $record = $approval->approvalable;

        return $record
            ? Gate::forUser($user)->allows('update', $record)
            : Gate::forUser($user)->allows('create', $class);
    }

    public static function isRequestedBy(Approval $approval, Authenticatable $user): bool
    {
        return filled($approval->getAttribute('creator_id'))
            && (string) $approval->getAttribute('creator_id') === (string) $user->getAuthIdentifier()
            && ($approval->getAttribute('creator_type') === null
                || $approval->getAttribute('creator_type') === $user::class
                || ($user instanceof Model && $approval->getAttribute('creator_type') === $user->getMorphClass()));
    }

    public static function isPending(Approval $approval): bool
    {
        return $approval->state === ApprovalStatus::Pending && blank($approval->getAttribute('custom_state'));
    }

    public static function canRollBack(Approval $approval): bool
    {
        return $approval->state === ApprovalStatus::Approved
            && $approval->rolled_back_at === null
            && $approval->approvalable !== null;
    }

    /**
     * @return class-string<Model>|null
     */
    public static function modelClass(Approval $approval): ?string
    {
        $type = $approval->getAttribute('approvalable_type');

        if (blank($type)) {
            return null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        /** @var class-string<Model>|null */
        return class_exists($class) ? $class : null;
    }

    /**
     * @return class-string<resource>|null
     */
    public static function resourceFor(Approval $approval): ?string
    {
        $class = static::modelClass($approval);

        if (! $class) {
            return null;
        }

        try {
            /** @var class-string<resource>|null */
            return Filament::getModelResource($class);
        } catch (Throwable) {
            return null;
        }
    }

    public static function isCreation(Approval $approval): bool
    {
        return blank($approval->getAttribute('approvalable_id'));
    }

    public static function modelLabel(Approval $approval): string
    {
        if ($resource = static::resourceFor($approval)) {
            return Str::ucfirst($resource::getModelLabel());
        }

        $class = static::modelClass($approval) ?? (string) $approval->getAttribute('approvalable_type');

        return Str::headline(class_basename($class));
    }

    public static function recordTitle(Approval $approval): string
    {
        if (static::isCreation($approval)) {
            return __('filament-change-requests::change-requests.record.new', ['model' => static::modelLabel($approval)]);
        }

        $record = $approval->approvalable;

        if (! $record) {
            return __('filament-change-requests::change-requests.record.deleted', [
                'model' => static::modelLabel($approval),
                'id' => $approval->getAttribute('approvalable_id'),
            ]);
        }

        if ($resource = static::resourceFor($approval)) {
            $title = $resource::getRecordTitle($record);

            if (filled($title) && (string) $title !== (string) $record->getKey()) {
                return (string) $title;
            }
        }

        return static::modelLabel($approval).' #'.$record->getKey();
    }

    public static function recordUrl(Approval $approval): ?string
    {
        $record = $approval->approvalable;
        $resource = static::resourceFor($approval);

        if (! $record || ! $resource) {
            return null;
        }

        try {
            foreach (['view', 'edit'] as $page) {
                $allowed = $page === 'view' ? $resource::canView($record) : $resource::canEdit($record);

                if ($resource::hasPage($page) && $allowed) {
                    return $resource::getUrl($page, ['record' => $record]);
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    public static function requestorName(Approval $approval): ?string
    {
        if (blank($approval->getAttribute('creator_id'))) {
            return null;
        }

        try {
            $creator = $approval->getRelationValue('creator');
        } catch (Throwable) {
            $creator = null;
        }

        return $creator instanceof Model ? static::userName($creator) : '#'.$approval->getAttribute('creator_id');
    }

    public static function reviewerName(Approval $approval): ?string
    {
        $id = $approval->getAttribute('audited_by');

        if (blank($id)) {
            return null;
        }

        /** @var class-string<Model>|null $class */
        $class = config('auth.providers.users.model');
        $user = $class && class_exists($class) ? $class::query()->find($id) : null;

        return $user ? static::userName($user) : '#'.$id;
    }

    public static function userName(Model $user): string
    {
        if ($user instanceof HasName) {
            return $user->getFilamentName();
        }

        return (string) ($user->getAttribute('name') ?? $user->getAttribute('email') ?? '#'.$user->getKey());
    }

    /**
     * The status key: approved, rejected, pending, rolled_back or a custom state from config('approval.states').
     */
    public static function status(Approval $approval): string
    {
        if ($approval->rolled_back_at !== null && $approval->state === ApprovalStatus::Approved) {
            return 'rolled_back';
        }

        return $approval->getState();
    }

    public static function statusLabel(string $status): string
    {
        $key = 'filament-change-requests::change-requests.status.'.$status;
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return (string) (config("approval.states.{$status}.name") ?? Str::headline($status));
    }

    /**
     * Badge colour. Badges are drawn as translucent fills in dark mode, where a warm (amber or red) fill turns brown,
     * so pending is blue and rejected is gray with an icon.
     */
    public static function statusColor(string $status): string
    {
        return match ($status) {
            'approved' => 'success',
            'rejected', 'rolled_back' => 'gray',
            default => 'info',
        };
    }

    public static function statusIcon(string $status): Heroicon
    {
        return match ($status) {
            'approved' => Heroicon::OutlinedCheckCircle,
            'rejected' => Heroicon::OutlinedXCircle,
            'rolled_back' => Heroicon::OutlinedArrowUturnLeft,
            default => Heroicon::OutlinedClock,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        $states = array_keys((array) config('approval.states', []));

        return collect(array_unique(['pending', 'approved', 'rejected', ...$states]))
            ->mapWithKeys(fn (string $state): array => [$state => static::statusLabel($state)])
            ->all();
    }

    public static function attributeLabel(string $attribute): string
    {
        return static::plugin()->getAttributeLabels()[$attribute] ?? Str::headline($attribute);
    }

    /**
     * @return array<int, string>
     */
    public static function changedAttributes(Approval $approval): array
    {
        $new = static::toArray($approval->new_data);

        return array_map('strval', array_keys($new));
    }

    /**
     * One row per changed attribute: label, old value, new value, whether the value changed.
     *
     * @return array<int, array{attribute: string, label: string, old: string, new: string, changed: bool, masked: bool}>
     */
    public static function diff(Approval $approval): array
    {
        $new = static::toArray($approval->new_data);
        $old = static::toArray($approval->original_data);
        $isCreation = static::isCreation($approval);
        $masked = static::plugin()->getMaskedAttributes();

        $rows = [];

        foreach (array_unique([...array_keys($new), ...array_keys($old)]) as $attribute) {
            $attribute = (string) $attribute;
            $isMasked = in_array($attribute, $masked, true);
            $oldValue = $old[$attribute] ?? null;
            $newValue = $new[$attribute] ?? null;

            $rows[] = [
                'attribute' => $attribute,
                'label' => static::attributeLabel($attribute),
                'old' => $isCreation && ! array_key_exists($attribute, $old)
                    ? ''
                    : ($isMasked ? static::MASK : static::formatValue($oldValue)),
                'new' => $isMasked ? static::MASK : static::formatValue($newValue),
                'changed' => $isMasked || static::normalize($oldValue) !== static::normalize($newValue),
                'masked' => $isMasked,
            ];
        }

        return $rows;
    }

    public static function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => '—',
            is_bool($value) => $value ? __('filament-change-requests::change-requests.value.yes') : __('filament-change-requests::change-requests.value.no'),
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_array($value), is_object($value) => (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }

    protected static function normalize(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function toArray(mixed $data): array
    {
        if ($data instanceof \ArrayObject) {
            return $data->getArrayCopy();
        }

        return is_array($data) ? $data : [];
    }
}
