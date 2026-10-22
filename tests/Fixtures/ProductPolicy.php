<?php

declare(strict_types=1);

namespace AmjadIqbal\FilamentChangeRequests\Tests\Fixtures;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->name !== 'Intern';
    }

    public function update(User $user, Product $product): bool
    {
        return $user->name !== 'Intern';
    }
}
