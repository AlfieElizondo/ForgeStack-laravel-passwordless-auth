<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Tests\Support;

use ForgeStack\PasswordlessAuth\Contracts\AccessPolicy;
use ForgeStack\PasswordlessAuth\Contracts\LoginLinkSender;
use ForgeStack\PasswordlessAuth\Contracts\SubjectDirectory;

/**
 * Doble de prueba para los tres contratos que implementa la aplicación.
 */
final class InMemorySubjects implements SubjectDirectory, AccessPolicy, LoginLinkSender
{
    /** @var array<string, string> email => subject id */
    public array $subjects = [];

    /** @var array<string, true> subject ids con acceso vigente */
    public array $allowed = [];

    /** @var list<array{email: string, token: string}> */
    public array $sent = [];

    public function findSubjectId(string $email): ?string
    {
        return $this->subjects[$email] ?? null;
    }

    public function canSignIn(string $subjectId): bool
    {
        return isset($this->allowed[$subjectId]);
    }

    public function send(string $email, string $token, \DateTimeInterface $expiresAt): void
    {
        $this->sent[] = ['email' => $email, 'token' => $token];
    }
}
