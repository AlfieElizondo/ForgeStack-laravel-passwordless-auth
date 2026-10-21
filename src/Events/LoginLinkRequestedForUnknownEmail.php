<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Events;

/**
 * Solo lleva el hash del correo: los oyentes no reciben el correo en claro.
 */
final readonly class LoginLinkRequestedForUnknownEmail
{
    public function __construct(public string $emailHash) {}
}
