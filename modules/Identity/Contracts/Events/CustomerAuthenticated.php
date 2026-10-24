<?php

namespace Modules\Identity\Contracts\Events;

/** Dikirim sinkron setelah login berhasil (session sudah di-regenerate). */
final readonly class CustomerAuthenticated
{
    public function __construct(public string $customerId) {}
}
