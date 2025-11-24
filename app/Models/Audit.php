<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Models\Audit as PackageAuditModel;

/**
 * @property int $id
 * @property string|null $user_type
 * @property string|null $user_id
 * @property string $event
 * @property string $auditable_type
 * @property string $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $tags
 * @property string|null $unlock_request_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read UnlockRequest|null $unlockRequest
 *
 * @method static AuditFactory factory()
 */
class Audit extends PackageAuditModel
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory;

    /**
     * Get the unlock request associated with this audit
     */
    public function unlockRequest()
    {
        return $this->belongsTo(UnlockRequest::class, 'unlock_request_id');
    }

    /**
     * Scope to filter audits by unlock request
     */
    public function scopeForUnlockRequest($query, string $unlockRequestId)
    {
        return $query->where('unlock_request_id', $unlockRequestId);
    }

    /**
     * Scope to filter audits that used unlock permission
     */
    public function scopeWithUnlockRequest($query)
    {
        return $query->whereNotNull('unlock_request_id');
    }
}
