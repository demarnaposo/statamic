<?php

namespace Modules\Identity\Contracts\Events;

/** Dikirim sinkron setelah akun dibuat dan customer langsung login. */
final readonly class CustomerRegistered
{
    public function __construct(public string $customerId) {}
}
