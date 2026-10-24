<?php

namespace Modules\Assessment\Application;

use Illuminate\Contracts\Session\Session;

/** attempt_token yang menunggu customer login/daftar, disimpan di session. */
final class PendingClaim
{
    private const KEY = 'assessment.pending_token';

    public function __construct(private readonly Session $session) {}

    public function remember(string $token): void
    {
        $this->session->put(self::KEY, $token);
    }

    public function has(): bool
    {
        return $this->session->has(self::KEY);
    }

    public function pull(): ?string
    {
        $token = $this->session->pull(self::KEY);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function isWellFormed(mixed $token): bool
    {
        return is_string($token) && preg_match('/^[A-Za-z0-9]{20,100}$/', $token) === 1;
    }
}
