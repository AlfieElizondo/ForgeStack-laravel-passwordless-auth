<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Events;

final readonly class TrustedDeviceRevoked
{
    public function __construct(public string $subjectId, public string $deviceId) {}
}
