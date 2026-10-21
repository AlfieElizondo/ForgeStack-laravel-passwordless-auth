<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Contracts;

/**
 * Busca al sujeto (usuario, cliente, persona) que corresponde a un correo.
 * La aplicación decide qué entidades participan y en qué orden.
 */
interface SubjectDirectory
{
    public function findSubjectId(string $email): ?string;
}
