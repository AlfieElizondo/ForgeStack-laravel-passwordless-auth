<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Actions;

use Carbon\CarbonImmutable;
use ForgeStack\PasswordlessAuth\Contracts\AccessPolicy;
use ForgeStack\PasswordlessAuth\Enums\LoginLinkRejection;
use ForgeStack\PasswordlessAuth\Events\LoginLinkConsumed;
use ForgeStack\PasswordlessAuth\Events\LoginLinkRefused;
use ForgeStack\PasswordlessAuth\Exceptions\LoginLinkRejected;
use ForgeStack\PasswordlessAuth\Models\LoginLink;
use ForgeStack\PasswordlessAuth\Support\Token;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Consume un enlace una sola vez, antes de que venza y solo mientras el sujeto
 * siga teniendo acceso. Las vistas previas (escáneres de correo) pueden usar
 * rejectionFor(), que no gasta el enlace.
 */
final class ConsumeLoginLink
{
    public function __construct(
        private readonly AccessPolicy $accessPolicy,
        private readonly Dispatcher $events,
    ) {}

    public function rejectionFor(string $token): ?LoginLinkRejection
    {
        $link = LoginLink::query()->where('token_hash', Token::hash($token))->first();

        if ($link === null) {
            return LoginLinkRejection::Invalid;
        }

        return $this->rejectionOf($link);
    }

    /**
     * @return string El id del sujeto que entra.
     *
     * @throws LoginLinkRejected
     */
    public function handle(string $token): string
    {
        $link = LoginLink::query()->where('token_hash', Token::hash($token))->first();

        if ($link === null) {
            $this->refuse(LoginLinkRejection::Invalid, null, null);
        }

        $rejection = $this->rejectionOf($link);

        if ($rejection !== null) {
            $this->refuse($rejection, $link->id, $link->subject_id);
        }

        // Marcar como usado solo si nadie lo hizo antes: evita dos entradas en carrera.
        $marked = LoginLink::query()
            ->whereKey($link->id)
            ->whereNull('used_at')
            ->update(['used_at' => CarbonImmutable::now()]);

        if ($marked !== 1) {
            $this->refuse(LoginLinkRejection::Used, $link->id, $link->subject_id);
        }

        $this->events->dispatch(new LoginLinkConsumed($link->subject_id, $link->id));

        return $link->subject_id;
    }

    private function rejectionOf(LoginLink $link): ?LoginLinkRejection
    {
        if ($link->used_at !== null) {
            return LoginLinkRejection::Used;
        }

        if ($link->expires_at->lessThanOrEqualTo(CarbonImmutable::now())) {
            return LoginLinkRejection::Expired;
        }

        if (! $this->accessPolicy->canSignIn($link->subject_id)) {
            return LoginLinkRejection::NoAccess;
        }

        return null;
    }

    private function refuse(LoginLinkRejection $reason, ?string $loginLinkId, ?string $subjectId): never
    {
        $this->events->dispatch(new LoginLinkRefused($reason, $loginLinkId, $subjectId));

        throw new LoginLinkRejected($reason);
    }
}
