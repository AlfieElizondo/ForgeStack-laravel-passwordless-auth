<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Exceptions;

use ForgeStack\PasswordlessAuth\Enums\LoginLinkRejection;
use RuntimeException;

final class LoginLinkRejected extends RuntimeException
{
    public function __construct(public readonly LoginLinkRejection $reason)
    {
        parent::__construct("Login link rejected: {$reason->value}");
    }
}
