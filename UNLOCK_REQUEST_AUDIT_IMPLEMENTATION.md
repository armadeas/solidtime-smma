# Implementasi Sistem Unlock Request dengan Dual Unlock & Audit Log

## Ringkasan Fitur

Sistem ini menambahkan dua fitur utama:

### 1. Dual Unlock Validation untuk Perubahan Project
Ketika mengubah time entry dari Project A ke Project B yang sudah dalam periode lock:
- Membutuhkan **2 unlock request aktif**: satu untuk project lama dan satu untuk project baru
- Validasi dilakukan di middleware `CheckTimeEntryLock`
- Response error khusus jika dual unlock tidak terpenuhi

### 2. Audit Log untuk Aktivitas Unlock Request
Setiap perubahan data (tambah/ubah/hapus) yang menggunakan unlock permission akan dicatat:
- Mencatat action: `create`, `update`, `delete`
- Menyimpan old values dan new values untuk perbandingan
- Menampilkan diff/perubahan data seperti commit log
- Dapat dilihat di detail unlock request

## File-file yang Dibuat

### 1. Migration
**File:** `database/migrations/2025_11_13_070654_create_unlock_request_audit_logs_table.php`

Membuat tabel `unlock_request_audit_logs` dengan kolom:
- `id` - UUID primary key
- `unlock_request_id` - Foreign key ke unlock_requests
- `time_entry_id` - Foreign key ke time_entries (nullable)
- `member_id` - Foreign key ke members
- `action` - Jenis aksi: create/update/delete
- `old_values` - JSON nilai lama (untuk update/delete)
- `new_values` - JSON nilai baru (untuk create/update)
- `description` - Deskripsi human-readable
- `timestamps`

### 2. Model
**File:** `app/Models/UnlockRequestAuditLog.php`

Model untuk audit log dengan:
- Relationships ke UnlockRequest, TimeEntry, Member
- Method `getActionNameAttribute()` untuk nama action yang formatted
- Method `getFormattedChanges()` untuk menampilkan perubahan dalam format yang mudah dibaca

### 3. Service Updates
**File:** `app/Service/TimeEntryLockService.php`

Menambahkan method:
- `getActiveUnlock()` - Mendapatkan unlock request aktif
- `canModifyTimeEntry()` - Updated untuk dual unlock validation
- `logTimeEntryCreate()` - Log pembuatan time entry
- `logTimeEntryUpdate()` - Log perubahan time entry
- `logTimeEntryDelete()` - Log penghapusan time entry
- `getTimeEntryValues()` - Helper untuk capture nilai time entry

### 4. Middleware Updates
**File:** `app/Http/Middleware/CheckTimeEntryLock.php`

Update `handleUpdateTimeEntry()`:
- Deteksi perubahan project_id
- Validasi dual unlock untuk project lama dan baru
- Response error khusus dengan `requires_dual_unlock: true`

### 5. Controller Updates
**File:** `app/Http/Controllers/Api/V1/TimeEntryController.php`

Menambahkan audit logging di:
- `store()` - Log saat create time entry menggunakan unlock
- `update()` - Log saat update time entry menggunakan unlock (dengan old values)
- `destroy()` - Log saat delete time entry menggunakan unlock

**File:** `app/Http/Controllers/Api/V1/UnlockRequestController.php`

Update `show()`:
- Load relationship `auditLogs` dengan member dan timeEntry

### 6. Resources
**File:** `app/Http/Resources/V1/UnlockRequestAuditLog/UnlockRequestAuditLogResource.php`

Resource untuk menampilkan audit log dengan:
- Semua field audit log
- Formatted changes
- Member dan TimeEntry relationship

**File:** `app/Http/Resources/V1/UnlockRequest/UnlockRequestResource.php`

Update untuk include:
- `audit_logs` collection dalam response

## Cara Penggunaan

### 1. Dual Unlock untuk Perubahan Project

Ketika user ingin mengubah time entry yang locked dari Project A ke Project B:

```bash
# Request unlock untuk Project A (project lama)
POST /api/v1/organizations/{org_id}/unlock-requests
{
  "project_id": "project-a-uuid",
  "reason": "Need to move time entries"
}

# Request unlock untuk Project B (project baru)
POST /api/v1/organizations/{org_id}/unlock-requests
{
  "project_id": "project-b-uuid",
  "reason": "Need to move time entries"
}

# Manager approve kedua request

# Baru bisa update time entry
PUT /api/v1/organizations/{org_id}/time-entries/{entry_id}
{
  "project_id": "project-b-uuid"
}
```

Jika hanya salah satu yang ada unlock-nya, akan mendapat error:
```json
{
  "message": "Changing project requires active unlock permission for both the old and new projects.",
  "locked": true,
  "requires_dual_unlock": true,
  "old_project_id": "project-a-uuid",
  "new_project_id": "project-b-uuid"
}
```

### 2. Melihat Audit Log

```bash
# Get detail unlock request dengan audit logs
GET /api/v1/organizations/{org_id}/unlock-requests/{unlock_id}

# Response akan include:
{
  "id": "unlock-uuid",
  "project": {...},
  "status": "approved",
  "audit_logs": [
    {
      "id": "log-uuid",
      "action": "update",
      "action_name": "Updated",
      "description": "Updated time entry (changed: project_id, description) on 2025-11-10 14:30 for project \"Project B\"",
      "old_values": {
        "description": "Old task",
        "start": "2025-11-10T14:30:00Z",
        "project_id": "project-a-uuid",
        "project_name": "Project A"
      },
      "new_values": {
        "description": "New task", 
        "start": "2025-11-10T14:30:00Z",
        "project_id": "project-b-uuid",
        "project_name": "Project B"
      },
      "changes": {
        "description": {
          "old": "Old task",
          "new": "New task"
        },
        "project_id": {
          "old": "project-a-uuid",
          "new": "project-b-uuid"
        },
        "project_name": {
          "old": "Project A",
          "new": "Project B"
        }
      },
      "created_at": "2025-11-10T15:00:00Z",
      "member": {...}
    }
  ]
}
```

## Kapan Audit Log Dicatat

Audit log **hanya dicatat** ketika:
1. Time entry berada dalam periode lock (lebih dari X hari yang lalu sesuai setting)
2. User memiliki unlock permission aktif untuk project tersebut
3. User melakukan operasi create/update/delete

Jika time entry tidak dalam periode lock, tidak ada audit log yang dibuat.

## Database Schema

```sql
CREATE TABLE unlock_request_audit_logs (
    id UUID PRIMARY KEY,
    unlock_request_id UUID NOT NULL REFERENCES unlock_requests(id) ON DELETE CASCADE,
    time_entry_id UUID NULL REFERENCES time_entries(id) ON DELETE SET NULL,
    member_id UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    action VARCHAR(255) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    INDEX idx_unlock_audit_request_date (unlock_request_id, created_at),
    INDEX idx_unlock_audit_time_entry (time_entry_id)
);
```

## Testing

Untuk testing fitur ini:

1. **Set lock period di organization:**
   ```sql
   UPDATE organizations SET time_entry_lock_days = 3 WHERE id = 'org-uuid';
   ```

2. **Buat time entry lebih dari 3 hari lalu**

3. **Request unlock untuk project tersebut**

4. **Manager approve unlock request**

5. **Edit/delete time entry tersebut** - akan tercatat di audit log

6. **Coba pindah project** - akan butuh dual unlock

7. **Lihat audit log** di detail unlock request

## API Endpoints Terkait

- `GET /api/v1/organizations/{org}/unlock-requests` - List unlock requests (include audit count)
- `GET /api/v1/organizations/{org}/unlock-requests/{id}` - Detail dengan audit logs
- `POST /api/v1/organizations/{org}/unlock-requests` - Create unlock request
- `POST /api/v1/organizations/{org}/unlock-requests/{id}/approve` - Approve
- `POST /api/v1/organizations/{org}/unlock-requests/{id}/reject` - Reject

## Catatan Implementasi

1. **Performance**: Audit logs di-eager load hanya saat `show()`, tidak di `index()` untuk performa
2. **Cascade Delete**: Jika unlock request dihapus, audit logs ikut terhapus
3. **Null Safety**: Jika time entry dihapus, audit log tetap ada tapi `time_entry_id` jadi NULL
4. **History**: Audit log tidak bisa diedit/dihapus, bersifat immutable untuk integritas data
5. **Timezone**: Semua timestamp disimpan dalam UTC, formatting timezone dilakukan di frontend

## Migration Status

Migration sudah berhasil dijalankan:
```
✓ 2025_11_13_070654_create_unlock_request_audit_logs_table [Ran]
```

## Keamanan

- Audit log hanya bisa dilihat oleh:
  - Requester dari unlock request
  - Manager/Admin/Owner organization
- Time entry changes tetap perlu authorization permission
- Dual unlock memastikan tidak ada "backdoor" saat pindah project

