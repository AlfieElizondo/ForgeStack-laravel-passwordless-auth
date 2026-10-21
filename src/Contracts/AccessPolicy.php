<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Contracts;

/**
 * Decide si un sujeto puede entrar ahora. Se revisa al pedir el enlace y al
 * usarlo, de modo que retirar el acceso corta también los enlaces pendientes.
 */
interface AccessPolicy
{
    public function canSignIn(string $subjectId): bool;
}
