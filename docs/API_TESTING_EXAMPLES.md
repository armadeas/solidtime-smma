# API Testing Examples - Time Entry Lock Feature

Collection of API requests untuk testing fitur Time Entry Lock.

## Prerequisites

- Organization ID: `{org_id}`
- Project ID: `{project_id}`
- Auth Token: `Bearer {token}`
- Base URL: `http://localhost:80/api/v1`

---

## 1. Setup: Set Lock Days di Organization

### Update Organization Lock Days
```bash
curl -X PUT http://localhost:80/api/v1/organizations/{org_id} \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "time_entry_lock_days": 7
  }'
```

**Expected Response: 200 OK**
```json
{
  "data": {
    "id": "{org_id}",
    "name": "My Organization",
    "time_entry_lock_days": 7
  }
}
```

---

## 2. Test: Create Time Entry (Unlocked Date)

### Create Time Entry untuk hari ini (should work)
```bash
curl -X POST http://localhost:80/api/v1/organizations/{org_id}/time-entries \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "start": "2025-10-31T09:00:00Z",
    "end": "2025-10-31T17:00:00Z",
    "project_id": "{project_id}",
    "description": "Working on new feature"
  }'
```

**Expected Response: 201 Created**
```json
{
  "data": {
    "id": "{time_entry_id}",
    "start": "2025-10-31T09:00:00.000000Z",
    "end": "2025-10-31T17:00:00.000000Z"
  }
}
```

---

## 3. Test: Create Time Entry (Locked Date)

### Create Time Entry untuk 10 hari lalu (should fail)
```bash
curl -X POST http://localhost:80/api/v1/organizations/{org_id}/time-entries \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "start": "2025-10-21T09:00:00Z",
    "end": "2025-10-21T17:00:00Z",
    "project_id": "{project_id}",
    "description": "Old work entry"
  }'
```

**Expected Response: 403 Forbidden**
```json
{
  "message": "This date is locked. You need to request unlock permission from a project manager.",
  "locked": true,
  "lock_cutoff_date": "2025-10-24T00:00:00.000000Z"
}
```

---

## 4. Create Unlock Request

### Request unlock untuk project
```bash
curl -X POST http://localhost:80/api/v1/organizations/{org_id}/unlock-requests \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "{project_id}",
    "reason": "Forgot to log overtime hours last week"
  }'
```

**Expected Response: 201 Created**
```json
{
  "data": {
    "id": "{unlock_request_id}",
    "organization_id": "{org_id}",
    "project_id": "{project_id}",
    "requester_member_id": "{member_id}",
    "reason": "Forgot to log overtime hours last week",
    "status": "pending",
    "is_active": false,
    "created_at": "2025-10-31T10:00:00.000000Z"
  }
}
```

---

## 5. List Unlock Requests

### Employee: List my requests
```bash
curl -X GET "http://localhost:80/api/v1/organizations/{org_id}/unlock-requests?my_requests=true" \
  -H "Authorization: Bearer {token}"
```

### Manager: List pending approvals
```bash
curl -X GET "http://localhost:80/api/v1/organizations/{org_id}/unlock-requests?pending_approvals=true" \
  -H "Authorization: Bearer {token}"
```

### Filter by status
```bash
curl -X GET "http://localhost:80/api/v1/organizations/{org_id}/unlock-requests?status=pending" \
  -H "Authorization: Bearer {token}"
```

**Expected Response: 200 OK**
```json
{
  "data": [
    {
      "id": "{unlock_request_id}",
      "status": "pending",
      "project": {
        "id": "{project_id}",
        "name": "Project Alpha",
        "color": "#3B82F6"
      },
      "requester": {
        "id": "{member_id}",
        "name": "John Doe",
        "email": "john@example.com"
      },
      "reason": "Forgot to log overtime hours last week",
      "is_active": false,
      "created_at": "2025-10-31T10:00:00.000000Z"
    }
  ]
}
```

---

## 6. Approve Unlock Request (Manager)

### Approve request
```bash
curl -X POST http://localhost:80/api/v1/organizations/{org_id}/unlock-requests/{unlock_request_id}/approve \
  -H "Authorization: Bearer {manager_token}" \
  -H "Content-Type: application/json"
```

**Expected Response: 200 OK**
```json
{
  "data": {
    "id": "{unlock_request_id}",
    "status": "approved",
    "approver_member_id": "{manager_member_id}",
    "approver": {
      "id": "{manager_member_id}",
      "name": "Jane Manager",
      "email": "jane@example.com"
    },
    "approved_at": "2025-10-31T10:05:00.000000Z",
    "expires_at": "2025-10-31T10:35:00.000000Z",
    "is_active": true
  }
}
```

---

## 7. Create Time Entry (With Active Unlock)

### Now create the locked time entry (should work!)
```bash
curl -X POST http://localhost:80/api/v1/organizations/{org_id}/time-entries \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "start": "2025-10-21T09:00:00Z",
    "end": "2025-10-21T17:00:00Z",
    "project_id": "{project_id}",
    "description": "Overtime work from last week"
  }'
```

**Expected Response: 201 Created** ✅
```json
{
  "data": {
    "id": "{time_entry_id}",
    "start": "2025-10-21T09:00:00.000000Z",
    "end": "2025-10-21T17:00:00.000000Z",
    "project_id": "{project_id}"
  }
}
```

---

## 8. Update Locked Time Entry (With Active Unlock)

### Update existing locked time entry
```bash
curl -X PUT http://localhost:80/api/v1/organizations/{org_id}/time-entries/{locked_time_entry_id} \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "start": "2025-10-21T08:00:00Z",
    "end": "2025-10-21T18:00:00Z",
    "description": "Updated overtime hours"
  }'
```

**Expected Response: 200 OK** ✅

---

## 9. Reject Unlock Request (Manager)

### Reject a pending request
```bash
curl -X POST http://localhost:80/api/v1/organizations/{org_id}/unlock-requests/{unlock_request_id}/reject \
  -H "Authorization: Bearer {manager_token}" \
  -H "Content-Type: application/json"
```

**Expected Response: 200 OK**
```json
{
  "data": {
    "id": "{unlock_request_id}",
    "status": "rejected",
    "approver_member_id": "{manager_member_id}",
    "rejected_at": "2025-10-31T10:10:00.000000Z",
    "is_active": false
  }
}
```

---

## 10. Delete Unlock Request (Requester)

### Delete own pending request
```bash
curl -X DELETE http://localhost:80/api/v1/organizations/{org_id}/unlock-requests/{unlock_request_id} \
  -H "Authorization: Bearer {token}"
```

**Expected Response: 204 No Content**

---

## 11. Test After Unlock Expires

### Wait 30 minutes after approval, then try to edit
```bash
# This should fail with 403
curl -X PUT http://localhost:80/api/v1/organizations/{org_id}/time-entries/{locked_time_entry_id} \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Trying to edit after unlock expired"
  }'
```

**Expected Response: 403 Forbidden** ❌
```json
{
  "message": "This time entry is locked. You need to request unlock permission from a project manager.",
  "locked": true,
  "lock_cutoff_date": "2025-10-24T00:00:00.000000Z"
}
```

---

## 12. Get Single Unlock Request

### View specific unlock request
```bash
curl -X GET http://localhost:80/api/v1/organizations/{org_id}/unlock-requests/{unlock_request_id} \
  -H "Authorization: Bearer {token}"
```

**Expected Response: 200 OK**
```json
{
  "data": {
    "id": "{unlock_request_id}",
    "organization_id": "{org_id}",
    "project_id": "{project_id}",
    "project": {
      "id": "{project_id}",
      "name": "Project Alpha",
      "color": "#3B82F6"
    },
    "requester": {
      "id": "{member_id}",
      "name": "John Doe",
      "email": "john@example.com"
    },
    "approver": {
      "id": "{manager_member_id}",
      "name": "Jane Manager",
      "email": "jane@example.com"
    },
    "reason": "Forgot to log overtime hours last week",
    "status": "approved",
    "approved_at": "2025-10-31T10:05:00.000000Z",
    "expires_at": "2025-10-31T10:35:00.000000Z",
    "is_active": true,
    "created_at": "2025-10-31T10:00:00.000000Z",
    "updated_at": "2025-10-31T10:05:00.000000Z"
  }
}
```

---

## Test Scenarios Summary

### ✅ Should Work
1. Create/edit time entry untuk tanggal yang tidak locked
2. Create unlock request untuk project
3. Approve/reject unlock request (sebagai manager)
4. Create/edit locked time entry DENGAN active unlock
5. Delete own pending unlock request

### ❌ Should Fail (403)
1. Create time entry untuk tanggal yang locked TANPA unlock
2. Edit locked time entry TANPA active unlock
3. Delete locked time entry TANPA active unlock
4. Edit time entry setelah unlock expired (30 min)

### 🔐 Authorization Tests
1. Employee tidak bisa approve/reject requests
2. Manager hanya bisa approve projects mereka
3. Owner/Admin bisa approve semua projects
4. User hanya bisa delete pending requests mereka sendiri

---

## Postman Collection

Import collection ini ke Postman untuk testing:

```json
{
  "info": {
    "name": "Time Entry Lock API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:80/api/v1"
    },
    {
      "key": "token",
      "value": "YOUR_TOKEN_HERE"
    },
    {
      "key": "org_id",
      "value": "YOUR_ORG_ID"
    },
    {
      "key": "project_id",
      "value": "YOUR_PROJECT_ID"
    }
  ]
}
```

---

## Tips for Testing

1. **Setup Test Data**: Create organization dengan beberapa projects dan members
2. **Test Lock Days**: Mulai dengan 7 hari untuk testing
3. **Multiple Roles**: Test dengan akun Employee, Manager, dan Admin
4. **Edge Cases**: Test dengan unlock yang sudah expired
5. **Bulk Operations**: Test update/delete multiple time entries

---

## Common Errors & Solutions

### Error: "You already have an active or pending unlock request"
**Solution**: Wait for current request to be approved/rejected/expired, atau delete pending request

### Error: "Only pending requests can be approved"
**Solution**: Request sudah di-approve atau di-reject sebelumnya

### Error: "Project does not belong to organization"
**Solution**: Pastikan project_id valid dan belongs to organization

### Error: 403 "This date is locked"
**Solution**: Request unlock terlebih dahulu atau edit tanggal yang lebih recent

---

Happy Testing! 🚀

