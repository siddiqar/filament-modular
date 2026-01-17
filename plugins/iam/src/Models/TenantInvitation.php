<?php

namespace Sekeco\Iam\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TenantInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invited_by',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inviter(): BelongsTo
    {
        $userModel = config('iam.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'invited_by');
    }

    public function isPending(): bool
    {
        return is_null($this->accepted_at)
            && is_null($this->rejected_at)
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast()
            && is_null($this->accepted_at)
            && is_null($this->rejected_at);
    }

    public function isAccepted(): bool
    {
        return ! is_null($this->accepted_at);
    }

    public function isRejected(): bool
    {
        return ! is_null($this->rejected_at);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }
}
