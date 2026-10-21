<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Events;

final readonly class SessionRestoredFromDevice
{
    public function __construct(public string $subjectId, public string $deviceId) {}
}
