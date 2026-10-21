<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Enums;

enum LoginLinkRejection: string
{
    case Invalid = 'invalid';
    case Expired = 'expired';
    case Used = 'used';
    case NoAccess = 'no_access';
}
