<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UnlockRequestStatus;
use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $requester_member_id
 * @property string|null $approver_member_id
 * @property string|null $reason
 * @property UnlockRequestStatus $status
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Organization $organization
 * @property Project $project
 * @property Member $requester
 * @property Member|null $approver
 *
 * @method static Builder whereBelongsToOrganization(Organization $organization)
 * @method static Builder pending()
 * @method static Builder active()
 */
class UnlockRequest extends Model implements AuditableContract
{
    use CustomAuditable;
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'project_id',
        'requester_member_id',
        'approver_member_id',
        'reason',
        'status',
        'approved_at',
        'rejected_at',
        'expires_at',
    ];

    protected $casts = [
        'status' => UnlockRequestStatus::class,
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'requester_member_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'approver_member_id');
    }

    /**
     * Get all audits that were created using this unlock request
     * (Different from the audits() method from Auditable trait which tracks changes to this model)
     *
     * @return HasMany<Audit>
     */
    public function unlockAudits(): HasMany
    {
        return $this->hasMany(Audit::class, 'unlock_request_id');
    }

    /**
     * Approve the unlock request
     */
    public function approve(Member $approver): void
    {
        $this->status = UnlockRequestStatus::Approved;
        $this->approver_member_id = $approver->id;
        $this->approved_at = now();
        $this->expires_at = now()->addMinutes(30);
        $this->save();
    }

    /**
     * Reject the unlock request
     */
    public function reject(Member $approver): void
    {
        $this->status = UnlockRequestStatus::Rejected;
        $this->approver_member_id = $approver->id;
        $this->rejected_at = now();
        $this->save();
    }

    /**
     * Check if the unlock is still active
     */
    public function isActive(): bool
    {
        return $this->status === UnlockRequestStatus::Approved
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /**
     * Check if the unlock has expired
     */
    public function isExpired(): bool
    {
        return $this->status === UnlockRequestStatus::Approved
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    /**
     * Scope: Filter by organization
     *
     * @param  Builder<UnlockRequest>  $query
     * @return Builder<UnlockRequest>
     */
    public function scopeWhereBelongsToOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->id);
    }

    /**
     * Scope: Filter pending requests
     *
     * @param  Builder<UnlockRequest>  $query
     * @return Builder<UnlockRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', UnlockRequestStatus::Pending);
    }

    /**
     * Scope: Filter active (approved & not expired) requests
     *
     * @param  Builder<UnlockRequest>  $query
     * @return Builder<UnlockRequest>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UnlockRequestStatus::Approved)
            ->where('expires_at', '>', now());
    }
}
