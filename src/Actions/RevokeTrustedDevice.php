<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Actions;

use Carbon\CarbonImmutable;
use ForgeStack\PasswordlessAuth\Events\TrustedDeviceRevoked;
use ForgeStack\PasswordlessAuth\Models\TrustedDevice;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Solo el dueño del dispositivo puede revocarlo. Devuelve false si no es suyo o ya estaba revocado.
 */
final class RevokeTrustedDevice
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(string $subjectId, string $deviceId): bool
    {
        $revoked = TrustedDevice::query()
            ->whereKey($deviceId)
            ->where('subject_id', $subjectId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => CarbonImmutable::now()]);

        if ($revoked !== 1) {
            return false;
        }

        $this->events->dispatch(new TrustedDeviceRevoked($subjectId, $deviceId));

        return true;
    }
}
