<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Contracts;

use DateTimeInterface;

/**
 * Entrega el enlace de acceso. La aplicación construye la URL firmada y el correo.
 */
interface LoginLinkSender
{
    public function send(string $email, string $token, DateTimeInterface $expiresAt): void;
}
