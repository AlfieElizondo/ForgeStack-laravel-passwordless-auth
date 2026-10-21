<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Events;

use ForgeStack\PasswordlessAuth\Enums\LoginLinkRejection;

final readonly class LoginLinkRefused
{
    public function __construct(
        public LoginLinkRejection $reason,
        public ?string $loginLinkId,
        public ?string $subjectId,
    ) {}
}
