# SIMPB Frontend Integration Contract

Sistem Informasi Museum dan Perpustakaan Brawijaya  
Frontend Integration Contract - Draft Internal

---

## 1. Tujuan

Dokumen ini menjadi kontrak awal antara frontend dan backend SIMPB.

Dokumen ini mengatur:

1. struktur menu berdasarkan role,
2. mapping endpoint per halaman,
3. pola request API,
4. pola response dan error handling,
5. state management minimum,
6. pagination,
7. upload file,
8. checklist sebelum mulai membuat UI.

---

## 2. Base API

Development:

```text
http://127.0.0.1:8000/api
```

Semua request API wajib mengirim:

```http
Accept: application/json
```

Endpoint protected wajib mengirim:

```http
Authorization: Bearer {access_token}
```

## 3. Auth Contract

### Login

```http
POST /api/auth/login
```

Payload:

```json
{
  "email": "member@simpb.test",
  "password": "password",
  "device_name": "frontend-dev"
}
```

Response sukses:

```json
{
  "message": "Login berhasil.",
  "token_type": "Bearer",
  "access_token": "...",
  "user": {
    "id": 4,
    "name": "Member SIMPB",
    "email": "member@simpb.test",
    "roles": ["member"]
  }
}
```

Frontend menyimpan:

| State | Isi |
| --- | --- |
| auth.token | access_token |
| auth.user | object user |
| auth.roles | array role dari user |
| auth.isAuthenticated | true/false |

### Me

```http
GET /api/auth/me
```

Dipakai untuk validasi token saat aplikasi pertama kali dibuka.

### Logout

```http
POST /api/auth/logout
```

Setelah logout sukses, frontend wajib menghapus token dan user state.

## 4. Standard Error Handling

Backend mengirim error dalam format:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Data yang diberikan tidak valid.",
    "details": {}
  },
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-05-20T00:00:00+00:00"
  }
}
```

Frontend wajib menangani:

| error.code | Tindakan UI |
| --- | --- |
| UNAUTHENTICATED | Hapus token, redirect ke login |
| FORBIDDEN | Tampilkan halaman/pesan akses ditolak |
| RESOURCE_NOT_FOUND | Tampilkan halaman data tidak ditemukan |
| ENDPOINT_NOT_FOUND | Tampilkan pesan endpoint tidak tersedia |
| VALIDATION_ERROR | Tampilkan error pada form |
| BUSINESS_RULE_VIOLATION | Tampilkan toast/alert aturan bisnis |
| RATE_LIMIT_EXCEEDED | Tampilkan pesan coba lagi nanti |
| INTERNAL_SERVER_ERROR | Tampilkan pesan server error |

## 5. Role-Based Menu Structure

### 5.1 Guest

Menu:

```
Beranda
Katalog
Kategori
Detail Koleksi
Login
```

Endpoint:

| Halaman | Endpoint |
| --- | --- |
| Katalog | GET /api/catalog/search |
| Kategori | GET /api/catalog/categories |
| Detail koleksi | GET /api/catalog/collections/{identifier} |

### 5.2 Member

Menu:

```
Dashboard
Katalog
Pinjaman Aktif
Riwayat Pinjaman
Reservasi
Bookmark
Profil
Logout
```

Endpoint:

| Halaman | Endpoint |
| --- | --- |
| Dashboard | GET /api/dashboard |
| Katalog | GET /api/catalog/search |
| Detail koleksi | GET /api/catalog/collections/{identifier} |
| Pinjaman aktif | GET /api/member/borrowings/active |
| Riwayat pinjaman | GET /api/member/borrowings/history |
| Daftar reservasi | GET /api/member/reservations |
| Buat reservasi | POST /api/member/reservations |
| Batalkan reservasi | POST /api/member/reservations/{reservation}/cancel |
| Daftar bookmark | GET /api/member/bookmarks |
| Tambah bookmark | POST /api/member/bookmarks |
| Update bookmark | PATCH /api/member/bookmarks/{identifier} |
| Hapus bookmark | DELETE /api/member/bookmarks/{identifier} |
| Profil login | GET /api/auth/me |
| Logout | POST /api/auth/logout |

### 5.3 Pustakawan

Menu:

```
Dashboard
Katalog Internal Library
Detail Koleksi Library
Tambah Koleksi Library
Metadata Koleksi
Digital Asset
Sirkulasi
Reservasi
Audit & Versi
Logout
```

Endpoint:

| Halaman | Endpoint |
| --- | --- |
| Dashboard | GET /api/dashboard |
| List koleksi internal | GET /api/staff/collections |
| Detail koleksi | GET /api/staff/collections/{identifier} |
| Create koleksi library | POST /api/staff/collections/library |
| Publish koleksi | POST /api/staff/collections/{identifier}/publish |
| Archive koleksi | POST /api/staff/collections/{identifier}/archive |
| Restore koleksi | POST /api/staff/collections/{identifier}/restore |
| Upsert metadata | PATCH /api/staff/collections/{identifier}/metadata |
| Delete metadata | DELETE /api/staff/collections/{identifier}/metadata/{metadata} |
| Attach creator | POST /api/staff/collections/{identifier}/creators |
| Detach creator | DELETE /api/staff/collections/{identifier}/creators/{creator} |
| Attach subject | POST /api/staff/collections/{identifier}/subjects |
| Detach subject | DELETE /api/staff/collections/{identifier}/subjects/{subject} |
| Register digital asset | POST /api/staff/collections/{identifier}/digital-assets |
| Upload digital asset | POST /api/staff/collections/{identifier}/digital-assets/upload |
| Download digital asset | GET /api/digital-assets/{asset}/download |
| Versions | GET /api/staff/collections/{identifier}/versions |
| Audit logs | GET /api/staff/collections/{identifier}/audit-logs |
| Allocate reservation copy | POST /api/circulation/reservations/{reservation}/allocate-copy |
| Update reservation status | PATCH /api/circulation/reservations/{reservation}/status |

Catatan:

- Pustakawan hanya boleh mengakses koleksi unit_type = library.
- Jika mencoba akses museum, backend akan mengembalikan 403.

### 5.4 Kurator

Menu:

```
Dashboard
Katalog Internal Museum
Detail Koleksi Museum
Tambah Koleksi Museum
Metadata Koleksi
Material
Condition Report
Digital Asset
Audit & Versi
Logout
```

Endpoint:

| Halaman | Endpoint |
| --- | --- |
| Dashboard | GET /api/dashboard |
| List koleksi internal | GET /api/staff/collections |
| Detail koleksi | GET /api/staff/collections/{identifier} |
| Create koleksi museum | POST /api/staff/collections/museum |
| Publish koleksi | POST /api/staff/collections/{identifier}/publish |
| Archive koleksi | POST /api/staff/collections/{identifier}/archive |
| Restore koleksi | POST /api/staff/collections/{identifier}/restore |
| Upsert metadata | PATCH /api/staff/collections/{identifier}/metadata |
| Delete metadata | DELETE /api/staff/collections/{identifier}/metadata/{metadata} |
| Attach creator | POST /api/staff/collections/{identifier}/creators |
| Detach creator | DELETE /api/staff/collections/{identifier}/creators/{creator} |
| Attach subject | POST /api/staff/collections/{identifier}/subjects |
| Detach subject | DELETE /api/staff/collections/{identifier}/subjects/{subject} |
| Register digital asset | POST /api/staff/collections/{identifier}/digital-assets |
| Upload digital asset | POST /api/staff/collections/{identifier}/digital-assets/upload |
| Download digital asset | GET /api/digital-assets/{asset}/download |
| Versions | GET /api/staff/collections/{identifier}/versions |
| Audit logs | GET /api/staff/collections/{identifier}/audit-logs |

Catatan:

- Kurator hanya boleh mengakses koleksi unit_type = museum.
- Jika mencoba akses library, backend akan mengembalikan 403.

### 5.5 Admin

Menu:

```
Dashboard
Katalog Internal Semua Unit
Tambah Koleksi Library
Tambah Koleksi Museum
Metadata
Digital Asset
Sirkulasi
Reservasi
Audit & Versi
Manajemen User
Logout
```

Endpoint:

Admin dapat memakai semua endpoint staff, member management internal yang sudah tersedia, dan seluruh endpoint katalog.

Catatan:

- Endpoint manajemen user UI belum dibuat.
- Jika UI admin butuh CRUD user, perlu tahap backend tambahan.

## 6. Page-to-Endpoint Mapping

### 6.1 Public Catalog Page

State minimum:

```javascript
{
  q: "",
  filters: {
    unit_type: "",
    collection_type: "",
    category_slug: "",
    year_from: "",
    year_to: "",
    subject: "",
    creator: ""
  },
  sort: "latest",
  page: 1,
  per_page: 10,
  loading: false,
  items: [],
  meta: null,
  error: null
}
```

Endpoint:

```http
GET /api/catalog/search
```

### 6.2 Collection Detail Page

Endpoint public:

```http
GET /api/catalog/collections/{identifier}
```

Endpoint staff:

```http
GET /api/staff/collections/{identifier}
```

Rule frontend:

- Guest/member memakai endpoint catalog.
- Staff memakai endpoint staff agar metadata internal, draft, dan archived bisa terlihat.

### 6.3 Staff Collection List Page

Endpoint:

```http
GET /api/staff/collections
```

Query:

```
q
unit_type
collection_type
publication_status
visibility
category_id
include_trashed
per_page
```

Role behavior:

- Admin boleh melihat semua unit.
- Pustakawan hanya library.
- Kurator hanya museum.

### 6.4 Staff Create Library Page

Endpoint:

```http
POST /api/staff/collections/library
```

Form section:

```
collection
library_item
copies
creators
subjects
metadata
digital_assets
```

Submit payload:

```json
{
  "reason": "Create via frontend",
  "collection": {},
  "library_item": {},
  "copies": [],
  "creators": [],
  "subjects": [],
  "metadata": [],
  "digital_assets": []
}
```

### 6.5 Staff Create Museum Page

Endpoint:

```http
POST /api/staff/collections/museum
```

Form section:

```
collection
museum_item
materials
creators
subjects
metadata
digital_assets
condition_reports
```

Submit payload:

```json
{
  "reason": "Create via frontend",
  "collection": {},
  "museum_item": {},
  "materials": [],
  "creators": [],
  "subjects": [],
  "metadata": [],
  "digital_assets": [],
  "condition_reports": []
}
```

### 6.6 Metadata Editor Page

Endpoint:

```http
PATCH /api/staff/collections/{identifier}/metadata
```

Payload:

```json
{
  "reason": "Update metadata",
  "metadata": [
    {
      "element_key": "dc.title",
      "value": "Judul Baru",
      "value_column": "value_string",
      "sort_order": 1,
      "source": "frontend"
    }
  ]
}
```

Delete:

```http
DELETE /api/staff/collections/{identifier}/metadata/{metadata}
```

### 6.7 Creator and Subject Editor

Creator:

```http
POST /api/staff/collections/{identifier}/creators
DELETE /api/staff/collections/{identifier}/creators/{creator}
```

Subject:

```http
POST /api/staff/collections/{identifier}/subjects
DELETE /api/staff/collections/{identifier}/subjects/{subject}
```

### 6.8 Digital Asset Manager

Register metadata asset:

```http
POST /api/staff/collections/{identifier}/digital-assets
```

Upload file fisik:

```http
POST /api/staff/collections/{identifier}/digital-assets/upload
```

Download:

```http
GET /api/digital-assets/{asset}/download
```

Upload memakai multipart/form-data.

### 6.9 Member Reservation Page

List:

```http
GET /api/member/reservations
```

Create:

```http
POST /api/member/reservations
```

Cancel:

```http
POST /api/member/reservations/{reservation}/cancel
```

### 6.10 Member Bookmark Page

List:

```http
GET /api/member/bookmarks
```

Create/update via POST:

```http
POST /api/member/bookmarks
```

Update:

```http
PATCH /api/member/bookmarks/{identifier}
```

Delete:

```http
DELETE /api/member/bookmarks/{identifier}
```

## 7. API Client Contract

Frontend API client wajib:

- otomatis menambahkan Accept: application/json,
- menambahkan Authorization: Bearer TOKEN jika token tersedia,
- bisa menangani JSON response,
- bisa menangani non-JSON response secara aman,
- melempar error terstruktur,
- otomatis trigger logout/redirect jika UNAUTHENTICATED.

## 8. State Handling Rules

### 8.1 Loading State

Setiap request UI minimal punya state:

```javascript
{
  loading: false,
  error: null,
  data: null
}
```

Saat request dimulai:

```javascript
loading = true
error = null
```

Saat sukses:

```javascript
loading = false
data = response.data
```

Saat gagal:

```javascript
loading = false
error = parsedError
```

### 8.2 Form Validation State

Untuk error VALIDATION_ERROR, mapping:

```javascript
formErrors = error.details
```

Contoh:

```json
{
  "email": ["The email field is required."],
  "password": ["The password field is required."]
}
```

Frontend menampilkan error di bawah field terkait.

### 8.3 Pagination State

Untuk endpoint list:

```javascript
{
  items: response.data,
  meta: response.meta,
  links: response.links
}
```

Frontend tidak menghitung total sendiri.

### 8.4 Auth State

State minimum:

```javascript
{
  token: null,
  user: null,
  roles: [],
  isAuthenticated: false
}
```

Derived helper:

```javascript
hasRole("admin")
hasAnyRole(["admin", "pustakawan"])
isStaff()
isMember()
```

### 8.5 Upload State

State minimum:

```javascript
{
  uploading: false,
  progress: 0,
  error: null,
  uploadedAsset: null
}
```

Catatan:

- Jika memakai fetch, progress upload tidak mudah didapat.
- Jika butuh progress upload, gunakan XMLHttpRequest atau library HTTP yang mendukung progress.
- Untuk tahap awal, upload tanpa progress masih cukup.

## 9. Frontend Access Guard

Guard halaman:

| Halaman | Guard |
| --- | --- |
| Login | guest only |
| Member dashboard | role member |
| Staff dashboard | role admin/pustakawan/kurator |
| Library staff pages | admin/pustakawan |
| Museum staff pages | admin/kurator |
| Admin-only pages | admin |
| Public catalog | public |

Pseudo-code:

```javascript
if (!auth.isAuthenticated && route.requiresAuth) {
  redirect("/login")
}

if (route.roles && !hasAnyRole(route.roles)) {
  showForbidden()
}
```

## 10. Minimal Frontend Route Plan

### Public

```
/
/catalog
/catalog/categories
/catalog/categories/:slug
/catalog/collections/:identifier
/login
```

### Member

```
/member/dashboard
/member/borrowings
/member/borrowings/history
/member/reservations
/member/bookmarks
/member/profile
```

### Staff Shared

```
/staff/dashboard
/staff/collections
/staff/collections/:identifier
/staff/collections/:identifier/metadata
/staff/collections/:identifier/assets
/staff/collections/:identifier/audit
/staff/collections/:identifier/versions
```

### Pustakawan

```
/staff/library/create
/staff/circulation/reservations
```

### Kurator

```
/staff/museum/create
```

### Admin

```
/admin/dashboard
/admin/collections
```

Catatan: route admin user management belum didukung backend secara khusus.

## 11. Readiness Checklist Before UI Coding

### Backend

- php artisan about tanpa error.
- php artisan route:list --path=api sudah disimpan.
- API auth login berhasil.
- API dashboard berhasil untuk admin, pustakawan, kurator, member.
- API catalog search berhasil.
- API staff collection list berhasil.
- API upload asset berhasil.
- Error JSON standar sudah aktif.
- Rate limit login sudah aktif.

### Frontend Contract

- Role menu disepakati.
- Route frontend disepakati.
- API client helper tersedia.
- Error parser helper tersedia.
- Token storage strategy disepakati.
- Pagination handling disepakati.
- Upload strategy disepakati.
- Forbidden/unauthenticated behavior disepakati.

## 12. Known Frontend Gaps

Backend saat ini belum menyediakan endpoint khusus untuk:

- CRUD user admin,
- CRUD kategori/lokasi/reference data dari UI,
- update detail spesifik library_items,
- update detail spesifik museum_items,
- delete digital asset,
- generate thumbnail asli,
- laporan statistik lanjutan,
- export/import metadata.

Frontend tahap awal sebaiknya fokus pada:

- login,
- dashboard,
- katalog,
- detail koleksi,
- member borrowings/reservations/bookmarks,
- staff list/detail collection,
- create collection,
- metadata maintenance,
- digital asset upload.
