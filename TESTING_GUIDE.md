# 🧪 Quick Testing Guide - Unlock Request Audit & Dual Unlock

## Setup Test Environment

```bash
# 1. Access Laravel Tinker
docker compose exec laravel.test php artisan tinker

# 2. Set lock period (3 days) untuk testing
$org = \App\Models\Organization::first();
$org->time_entry_lock_days = 3;
$org->save();
echo "✓ Lock period set to 3 days";
```

## Test Case 1: Dual Unlock Validation

### Scenario: Pindah Project Locked Time Entry

```bash
# Setup via Tinker
$member = \App\Models\Member::first();
$projectA = \App\Models\Project::where('name', 'Project A')->first();
$projectB = \App\Models\Project::where('name', 'Project B')->first();

# Create time entry 5 hari lalu di Project A
$timeEntry = \App\Models\TimeEntry::create([
    'member_id' => $member->id,
    'user_id' => $member->user_id,
    'organization_id' => $member->organization_id,
    'project_id' => $projectA->id,
    'description' => 'Test locked entry',
    'start' => now()->subDays(5),
    'end' => now()->subDays(5)->addHours(2),
    'billable' => true,
]);
echo "✓ Created locked time entry: " . $timeEntry->id;
```

### Test via API

```bash
# Test 1: Try to change project WITHOUT unlock (should FAIL)
curl -X PUT "http://localhost:8083/api/v1/organizations/{org_id}/time-entries/{entry_id}" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "{project_b_id}"
  }'

# Expected Response:
# {
#   "message": "Time entry is locked...",
#   "locked": true
# }
```

```bash
# Test 2: Create unlock for Project A only
curl -X POST "http://localhost:8083/api/v1/organizations/{org_id}/unlock-requests" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "{project_a_id}",
    "reason": "Need to move time entries"
  }'

# Approve as manager
curl -X POST "http://localhost:8083/api/v1/organizations/{org_id}/unlock-requests/{unlock_id}/approve" \
  -H "Authorization: Bearer {manager_token}"

# Try to change project (should still FAIL - need dual unlock)
curl -X PUT "http://localhost:8083/api/v1/organizations/{org_id}/time-entries/{entry_id}" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "{project_b_id}"
  }'

# Expected Response:
# {
#   "message": "Changing project requires active unlock permission for both the old and new projects.",
#   "locked": true,
#   "requires_dual_unlock": true,
#   "old_project_id": "{project_a_id}",
#   "new_project_id": "{project_b_id}"
# }
```

```bash
# Test 3: Create unlock for Project B also
curl -X POST "http://localhost:8083/api/v1/organizations/{org_id}/unlock-requests" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "{project_b_id}",
    "reason": "Need to move time entries"
  }'

# Approve as manager
curl -X POST "http://localhost:8083/api/v1/organizations/{org_id}/unlock-requests/{unlock_id}/approve" \
  -H "Authorization: Bearer {manager_token}"

# Now try to change project (should SUCCESS + create audit log)
curl -X PUT "http://localhost:8083/api/v1/organizations/{org_id}/time-entries/{entry_id}" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "{project_b_id}",
    "description": "Moved to Project B"
  }'

# Expected Response:
# {
#   "id": "{entry_id}",
#   "project_id": "{project_b_id}",
#   "description": "Moved to Project B",
#   ...
# }
```

---

## Test Case 2: Audit Logging

### Test CREATE with Unlock

```bash
# Create time entry 5 hari lalu (requires unlock)
curl -X POST "http://localhost:8083/api/v1/organizations/{org_id}/time-entries" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "member_id": "{member_id}",
    "project_id": "{project_id}",
    "description": "Created with unlock",
    "start": "2025-11-08T10:00:00Z",
    "end": "2025-11-08T12:00:00Z",
    "billable": true
  }'

# Check audit log created
curl -X GET "http://localhost:8083/api/v1/organizations/{org_id}/unlock-requests/{unlock_id}" \
  -H "Authorization: Bearer {token}"

# Expected in response:
# {
#   "audit_logs": [
#     {
#       "action": "create",
#       "action_name": "Created",
#       "new_values": {
#         "description": "Created with unlock",
#         "start": "2025-11-08T10:00:00Z",
#         ...
#       },
#       "description": "Created time entry on 2025-11-08 10:00 for project \"...\""
#     }
#   ]
# }
```

### Test UPDATE with Unlock

```bash
# Update locked time entry
curl -X PUT "http://localhost:8083/api/v1/organizations/{org_id}/time-entries/{entry_id}" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Updated description",
    "billable": false
  }'

# Check audit log
curl -X GET "http://localhost:8083/api/v1/organizations/{org_id}/unlock-requests/{unlock_id}" \
  -H "Authorization: Bearer {token}"

# Expected:
# {
#   "audit_logs": [
#     {
#       "action": "update",
#       "action_name": "Updated",
#       "old_values": {
#         "description": "Old description",
#         "billable": true
#       },
#       "new_values": {
#         "description": "Updated description",
#         "billable": false
#       },
#       "changes": {
#         "description": {
#           "old": "Old description",
#           "new": "Updated description"
#         },
#         "billable": {
#           "old": true,
#           "new": false
#         }
#       }
#     }
#   ]
# }
```

### Test DELETE with Unlock

```bash
# Delete locked time entry
curl -X DELETE "http://localhost:8083/api/v1/organizations/{org_id}/time-entries/{entry_id}" \
  -H "Authorization: Bearer {token}"

# Check audit log
curl -X GET "http://localhost:8083/api/v1/organizations/{org_id}/unlock-requests/{unlock_id}" \
  -H "Authorization: Bearer {token}"

# Expected:
# {
#   "audit_logs": [
#     {
#       "action": "delete",
#       "action_name": "Deleted",
#       "old_values": {
#         "description": "...",
#         "start": "...",
#         ...
#       },
#       "new_values": null,
#       "description": "Deleted time entry on ... for project \"...\""
#     }
#   ]
# }
```

---

## Test Case 3: Database Verification

```bash
# Via Tinker
docker compose exec laravel.test php artisan tinker

# Check audit logs in database
\App\Models\UnlockRequestAuditLog::with(['unlockRequest', 'member', 'timeEntry'])->get();

# Check specific unlock request's audit logs
$unlock = \App\Models\UnlockRequest::find('{unlock_id}');
$unlock->auditLogs;

# Count audit logs per unlock
\App\Models\UnlockRequest::withCount('auditLogs')->get();

# Get formatted changes
$auditLog = \App\Models\UnlockRequestAuditLog::first();
$auditLog->getFormattedChanges();
```

---

## Test Case 4: Edge Cases

### 4.1 Expired Unlock
```bash
# Set unlock to expired
$unlock = \App\Models\UnlockRequest::find('{unlock_id}');
$unlock->expires_at = now()->subMinutes(5);
$unlock->save();

# Try to modify time entry (should FAIL)
curl -X PUT "http://localhost:8083/api/v1/organizations/{org_id}/time-entries/{entry_id}" \
  -H "Authorization: Bearer {token}" \
  -d '{"description": "Should fail"}'
```

### 4.2 Unlock for Different Project
```bash
# Create unlock for Project A
# Try to modify time entry in Project B (should FAIL)
```

### 4.3 Non-Locked Entry (No Audit Log)
```bash
# Create time entry TODAY (not locked)
curl -X POST "http://localhost:8083/api/v1/organizations/{org_id}/time-entries" \
  -d '{
    "start": "2025-11-14T10:00:00Z",
    ...
  }'

# Modify it - should NOT create audit log
# Check unlock request - audit_logs should be empty
```

---

## Verification Checklist

After running tests, verify:

- [ ] ✓ Dual unlock validation works for project changes
- [ ] ✓ Single unlock is NOT enough for project change
- [ ] ✓ Both unlocks allow project change
- [ ] ✓ Error message shows `requires_dual_unlock: true`
- [ ] ✓ CREATE action logged with new_values
- [ ] ✓ UPDATE action logged with old_values and new_values
- [ ] ✓ DELETE action logged with old_values
- [ ] ✓ Changes/diff calculated correctly
- [ ] ✓ Human-readable description generated
- [ ] ✓ Non-locked entries don't create audit logs
- [ ] ✓ Expired unlocks don't allow changes
- [ ] ✓ Audit logs visible in unlock request detail
- [ ] ✓ Cascade delete works (delete unlock = delete audit logs)
- [ ] ✓ Null safety works (delete time entry = audit log kept)

---

## Quick Commands Reference

```bash
# Clear all caches
docker compose exec laravel.test php artisan cache:clear
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan route:clear

# Check migration status
docker compose exec laravel.test php artisan migrate:status | grep unlock

# View routes
docker compose exec laravel.test php artisan route:list | grep unlock

# Run tinker
docker compose exec laravel.test php artisan tinker

# Check logs
docker compose exec laravel.test tail -f storage/logs/laravel.log

# Database queries
docker compose exec laravel.test php artisan tinker
>>> \App\Models\UnlockRequestAuditLog::count();
>>> \App\Models\UnlockRequest::with('auditLogs')->get();
```

---

## Expected Database Records After Full Test

```sql
-- unlock_requests table
SELECT id, project_id, status, expires_at FROM unlock_requests;

-- unlock_request_audit_logs table
SELECT 
    id,
    unlock_request_id,
    action,
    description,
    created_at
FROM unlock_request_audit_logs
ORDER BY created_at DESC;

-- Should see entries for:
-- 1. CREATE action
-- 2. UPDATE action (project change)
-- 3. UPDATE action (description change)
-- 4. DELETE action
```

---

## Troubleshooting

### Issue: "Nothing to migrate"
```bash
# Check if already migrated
docker compose exec laravel.test php artisan migrate:status
# Look for: 2025_11_13_070654_create_unlock_request_audit_logs_table [Ran]
```

### Issue: "Class not found"
```bash
# Clear composer autoload
docker compose exec laravel.test composer dump-autoload
docker compose exec laravel.test php artisan clear-compiled
```

### Issue: "Column not found"
```bash
# Check table exists
docker compose exec laravel.test php artisan tinker
>>> \Schema::hasTable('unlock_request_audit_logs');
>>> \Schema::getColumnListing('unlock_request_audit_logs');
```

### Issue: Audit log not created
```bash
# Check conditions:
# 1. Is time entry locked? (> X days ago)
# 2. Is unlock active? (approved + not expired)
# 3. Is project_id set on time entry?

# Debug in tinker:
$lockService = app(\App\Service\TimeEntryLockService::class);
$timeEntry = \App\Models\TimeEntry::find('{id}');
$org = $timeEntry->organization;
$lockService->isTimeEntryLocked($timeEntry, $org); // Should return true
```

---

**Ready to test!** 🚀

