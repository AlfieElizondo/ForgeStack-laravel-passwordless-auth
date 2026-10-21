<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $subject_id
 * @property string $token_hash
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $used_at
 */
class LoginLink extends Model
{
    use HasUlids;

    protected $table = 'login_links';

    public $timestamps = false;

    protected $fillable = ['subject_id', 'token_hash', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
