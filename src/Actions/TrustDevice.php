<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Actions;

use Carbon\CarbonImmutable;
use ForgeStack\PasswordlessAuth\Events\DeviceTrusted;
use ForgeStack\PasswordlessAuth\Models\TrustedDevice;
use ForgeStack\PasswordlessAuth\Support\Token;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Recuerda un navegador. Devuelve el token en claro, que solo debe ir a la cookie.
 */
final class TrustDevice
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(string $subjectId, ?string $label = null): string
    {
        $token = Token::generate();

        $device = TrustedDevice::query()->create([
            'subject_id' => $subjectId,
            'token_hash' => Token::hash($token),
            'label' => $label,
            'last_used_at' => CarbonImmutable::now(),
        ]);

        $this->events->dispatch(new DeviceTrusted($subjectId, $device->id));

        return $token;
    }
}
