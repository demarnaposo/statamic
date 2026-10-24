<?php

namespace Modules\Identity\Application;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Modules\Identity\Contracts\CustomerDirectory;
use Modules\Identity\Contracts\Data\CustomerData;
use Modules\Identity\Domain\Customer;

final class EloquentCustomerDirectory implements CustomerDirectory
{
    public function __construct(private readonly AuthFactory $auth) {}

    public function find(string $customerId): ?CustomerData
    {
        return Customer::query()->find($customerId)?->toData();
    }

    public function current(): ?CustomerData
    {
        $user = $this->auth->guard('customer')->user();

        return $user instanceof Customer ? $user->toData() : null;
    }
}
