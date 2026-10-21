<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Support;

/**
 * Solo el hash se guarda en la base de datos; el token viaja únicamente en el correo.
 */
final class Token
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
