<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Actions;

use Carbon\CarbonImmutable;
use ForgeStack\PasswordlessAuth\Contracts\AccessPolicy;
use ForgeStack\PasswordlessAuth\Contracts\LoginLinkSender;
use ForgeStack\PasswordlessAuth\Contracts\SubjectDirectory;
use ForgeStack\PasswordlessAuth\Events\LoginLinkRequested;
use ForgeStack\PasswordlessAuth\Events\LoginLinkRequestedForUnknownEmail;
use ForgeStack\PasswordlessAuth\Models\LoginLink;
use ForgeStack\PasswordlessAuth\Support\Token;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Envía un enlace de acceso de un solo uso. Un correo desconocido o sin acceso
 * no recibe nada, y la respuesta es la misma para todos (anti-enumeración).
 */
final class RequestLoginLink
{
    public function __construct(
        private readonly SubjectDirectory $subjects,
        private readonly AccessPolicy $accessPolicy,
        private readonly LoginLinkSender $sender,
        private readonly Dispatcher $events,
    ) {}

    public function handle(string $email): void
    {
        $normalised = mb_strtolower(trim($email));
        $subjectId = $this->subjects->findSubjectId($normalised);

        if ($subjectId === null || ! $this->accessPolicy->canSignIn($subjectId)) {
            $this->events->dispatch(new LoginLinkRequestedForUnknownEmail(hash('sha256', $normalised)));

            return;
        }

        $token = Token::generate();
        $expiresAt = CarbonImmutable::now()->addMinutes((int) config('passwordless-auth.login_link_minutes', 15));

        $link = LoginLink::query()->create([
            'subject_id' => $subjectId,
            'token_hash' => Token::hash($token),
            'expires_at' => $expiresAt,
        ]);

        $this->sender->send($normalised, $token, $expiresAt);

        $this->events->dispatch(new LoginLinkRequested($subjectId, $link->id, $expiresAt));
    }
}
