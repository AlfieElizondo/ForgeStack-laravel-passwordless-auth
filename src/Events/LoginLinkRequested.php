<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Events;

use Carbon\CarbonImmutable;

final readonly class LoginLinkRequested
{
    public function __construct(
        public string $subjectId,
        public string $loginLinkId,
        public CarbonImmutable $expiresAt,
    ) {}
}
