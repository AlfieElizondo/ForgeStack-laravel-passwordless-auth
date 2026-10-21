<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Actions;

use ForgeStack\PasswordlessAuth\Models\TrustedDevice;
use Illuminate\Database\Eloquent\Collection;

/**
 * Dispositivos activos de un sujeto. Nunca devuelve los de otra persona.
 *
 * @return Collection<int, TrustedDevice>
 */
final class ListTrustedDevices
{
    public function handle(string $subjectId): Collection
    {
        return TrustedDevice::query()
            ->where('subject_id', $subjectId)
            ->whereNull('revoked_at')
            ->orderByDesc('last_used_at')
            ->get();
    }
}
