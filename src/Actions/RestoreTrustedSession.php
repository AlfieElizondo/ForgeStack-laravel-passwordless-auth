<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Actions;

use Carbon\CarbonImmutable;
use ForgeStack\PasswordlessAuth\Contracts\AccessPolicy;
use ForgeStack\PasswordlessAuth\Events\SessionRestoredFromDevice;
use ForgeStack\PasswordlessAuth\Models\TrustedDevice;
use ForgeStack\PasswordlessAuth\Support\Token;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Abre sesión desde un navegador recordado mientras no esté revocado, se haya
 * usado en los últimos N días y su sujeto siga teniendo acceso. El token rota en
 * cada uso; el anterior sigue válido 60 s para que dos pestañas no se cierren entre sí.
 */
final class RestoreTrustedSession
{
    public const GRACE_SECONDS = 60;

    public function __construct(
        private readonly AccessPolicy $accessPolicy,
        private readonly Dispatcher $events,
    ) {}

    /**
     * @return array{subject_id: string, device_id: string, token: ?string}|null
     *                                                                           token es null cuando se usó el token anterior dentro de la ventana de gracia.
     */
    public function handle(string $token): ?array
    {
        $now = CarbonImmutable::now();
        $hash = Token::hash($token);

        $device = TrustedDevice::query()->where('token_hash', $hash)->first();
        $isPrevious = false;

        if ($device === null) {
            $device = TrustedDevice::query()
                ->where('previous_token_hash', $hash)
                ->where('rotated_at', '>=', $now->subSeconds(self::GRACE_SECONDS))
                ->first();
            $isPrevious = true;
        }

        if ($device === null || ! $this->canRestore($device, $now)) {
            return null;
        }

        if ($isPrevious) {
            $device->forceFill(['last_used_at' => $now])->save();

            return $this->restored($device, null);
        }

        $newToken = Token::generate();

        $device->forceFill([
            'token_hash' => Token::hash($newToken),
            'previous_token_hash' => $hash,
            'rotated_at' => $now,
            'last_used_at' => $now,
        ])->save();

        return $this->restored($device, $newToken);
    }

    private function canRestore(TrustedDevice $device, CarbonImmutable $now): bool
    {
        if ($device->revoked_at !== null) {
            return false;
        }

        if ($device->last_used_at->lessThanOrEqualTo($now->subDays((int) config('passwordless-auth.trusted_device_days', 60)))) {
            return false;
        }

        return $this->accessPolicy->canSignIn($device->subject_id);
    }

    /**
     * @return array{subject_id: string, device_id: string, token: ?string}
     */
    private function restored(TrustedDevice $device, ?string $newToken): array
    {
        $this->events->dispatch(new SessionRestoredFromDevice($device->subject_id, $device->id));

        return ['subject_id' => $device->subject_id, 'device_id' => $device->id, 'token' => $newToken];
    }
}
