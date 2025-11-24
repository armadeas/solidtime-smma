# Time Entry Lock Feature

Fitur ini menambahkan batasan untuk edit, tambah, dan hapus time entry yang sudah lewat dari periode tertentu.

## Fitur Utama

### 1. Pengaturan Lock Days di Organization
- Admin/Owner dapat mengatur `time_entry_lock_days` di pengaturan organisasi
- Contoh: Jika diset 7 hari, maka time entry yang lebih dari 7 hari yang lalu tidak bisa diubah

### 2. Sistem Unlock Request
- User biasa dapat request unlock untuk project tertentu
- Request harus di-approve oleh manager project
- Unlock berlaku selama 30 menit setelah di-approve
- Manager yang bisa approve: Owner, Admin, atau Manager yang terdaftar di project tersebut

### 3. Validasi Otomatis
- Middleware `CheckTimeEntryLock` akan memvalidasi setiap operasi time entry
- Menolak operasi jika data sudah terkunci dan tidak ada unlock aktif

## API Endpoints

### Unlock Requests

#### 1. List Unlock Requests
```
GET /api/v1/organizations/{organization}/unlock-requests
```

Query parameters:
- `status`: pending|approved|rejected|expired
- `project_id`: Filter by project UUID
- `my_requests`: boolean - Show only my requests
- `pending_approvals`: boolean - Show pending requests for projects I manage

#### 2. Get Single Unlock Request
```
GET /api/v1/organizations/{organization}/unlock-requests/{unlockRequest}
```

#### 3. Create Unlock Request
```
POST /api/v1/organizations/{organization}/unlock-requests
```

Body:
```json
{
  "project_id": "uuid",
  "reason": "Alasan unlock (optional)"
}
```

#### 4. Approve Unlock Request
```
POST /api/v1/organizations/{organization}/unlock-requests/{unlockRequest}/approve
```

#### 5. Reject Unlock Request
```
POST /api/v1/organizations/{organization}/unlock-requests/{unlockRequest}/reject
```

#### 6. Delete Unlock Request
```
DELETE /api/v1/organizations/{organization}/unlock-requests/{unlockRequest}
```
(Hanya requester yang bisa delete pending request mereka sendiri)

## Database Schema

### Table: `organizations`
Menambah kolom:
- `time_entry_lock_days` (integer, nullable): Jumlah hari lock

### Table: `unlock_requests`
```
- id (uuid, primary)
- organization_id (uuid, foreign)
- project_id (uuid, foreign)
- requester_member_id (uuid, foreign)
- approver_member_id (uuid, foreign, nullable)
- reason (text, nullable)
- status (string): pending|approved|rejected|expired
- approved_at (timestamp, nullable)
- rejected_at (timestamp, nullable)
- expires_at (timestamp, nullable) - 30 menit dari approved_at
- created_at (timestamp)
- updated_at (timestamp)
```

## Models

### UnlockRequest
Path: `app/Models/UnlockRequest.php`

Methods:
- `approve(Member $approver)`: Approve request dan set expires_at
- `reject(Member $approver)`: Reject request
- `isActive()`: Check apakah unlock masih aktif
- `isExpired()`: Check apakah unlock sudah expired

Scopes:
- `whereBelongsToOrganization(Organization $organization)`
- `pending()`: Filter pending requests
- `active()`: Filter active (approved & not expired) requests

### Organization
Menambah property:
- `time_entry_lock_days` (int|null)

Menambah relationship:
- `unlockRequests()`: HasMany<UnlockRequest>

### Project
Menambah relationship:
- `unlockRequests()`: HasMany<UnlockRequest>

## Services

### TimeEntryLockService
Path: `app/Service/TimeEntryLockService.php`

Methods:
- `isTimeEntryLocked(TimeEntry $timeEntry, Organization $organization)`: bool
- `isDateLocked(Carbon $date, Organization $organization)`: bool
- `hasActiveUnlock(Member $member, Project $project)`: bool
- `canModifyTimeEntry(TimeEntry $timeEntry, Member $member)`: bool
- `canModifyDate(Carbon $date, Organization $organization, Member $member, ?Project $project)`: bool
- `getLockCutoffDate(Organization $organization)`: ?Carbon
- `createUnlockRequest(...)`: UnlockRequest
- `isProjectManager(Member $member, Project $project)`: bool

## Middleware

### CheckTimeEntryLock
Path: `app/Http/Middleware/CheckTimeEntryLock.php`

Diterapkan pada routes:
- POST `/time-entries`
- PUT `/time-entries/{timeEntry}`
- PATCH `/time-entries` (bulk update)
- DELETE `/time-entries/{timeEntry}`
- DELETE `/time-entries` (bulk delete)

Respon jika locked:
```json
{
  "message": "This time entry is locked. You need to request unlock permission from a project manager.",
  "locked": true,
  "lock_cutoff_date": "2025-10-25T00:00:00.000000Z"
}
```
HTTP Status: 403

## Policy

### UnlockRequestPolicy
Path: `app/Policies/UnlockRequestPolicy.php`

Authorization rules:
- `viewAny`: Semua member organization
- `view`: Requester atau manager project
- `create`: Semua member organization
- `approve`: Manager project (Owner, Admin, atau Manager yang terdaftar di project)
- `reject`: Manager project
- `delete`: Requester (hanya untuk pending requests)

## Enums

### UnlockRequestStatus
Path: `app/Enums/UnlockRequestStatus.php`

Values:
- `Pending`: Request menunggu approval
- `Approved`: Request di-approve, unlock aktif
- `Rejected`: Request ditolak
- `Expired`: Unlock sudah expired (otomatis setelah 30 menit)

## Role & Permissions

### Project Manager
Manager yang dapat approve unlock request:
1. **Owner**: Bisa manage semua project
2. **Admin**: Bisa manage semua project
3. **Manager**: Bisa manage project dimana mereka terdaftar sebagai member

## Workflow

### User Request Unlock
1. User coba edit time entry yang locked
2. Sistem return error 403 dengan info locked
3. User buat unlock request via API:
   ```
   POST /api/v1/organizations/{org}/unlock-requests
   {
     "project_id": "uuid",
     "reason": "Lupa input overtime kemarin"
   }
   ```
4. Request masuk dengan status `pending`

### Manager Approve/Reject
1. Manager list pending approvals:
   ```
   GET /api/v1/organizations/{org}/unlock-requests?pending_approvals=true
   ```
2. Manager approve:
   ```
   POST /api/v1/organizations/{org}/unlock-requests/{id}/approve
   ```
3. Request status jadi `approved`, `expires_at` = now() + 30 minutes

### User Edit Time Entry
1. User edit time entry dalam 30 menit
2. Middleware check: ada unlock aktif? Ya → allow
3. Time entry berhasil diupdate

### After 30 Minutes
1. Unlock otomatis expired (check via `isActive()` method)
2. User perlu request unlock lagi jika masih perlu edit

## Testing

Jalankan migration:
```bash
php artisan migrate
```

Test API dengan curl atau Postman menggunakan endpoint-endpoint di atas.

## Notes

- Unlock request bersifat per-project, bukan per-time-entry
- Durasi unlock fixed 30 menit (bisa diubah di method `approve()`)
- Lock days diset di level organization, berlaku untuk semua project
- Jika `time_entry_lock_days` = null, tidak ada lock (fitur disabled)

