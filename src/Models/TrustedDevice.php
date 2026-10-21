<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $subject_id
 * @property string $token_hash
 * @property string|null $previous_token_hash
 * @property string|null $label
 * @property \Illuminate\Support\Carbon $last_used_at
 * @property \Illuminate\Support\Carbon|null $rotated_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 */
class TrustedDevice extends Model
{
    use HasUlids;

    protected $table = 'trusted_devices';

    public $timestamps = false;

    protected $fillable = ['subject_id', 'token_hash', 'label', 'last_used_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'last_used_at' => 'datetime',
            'rotated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
