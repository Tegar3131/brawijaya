# SIMPB API Internal Documentation

Sistem Informasi Museum dan Perpustakaan Brawijaya  
Laravel API Documentation — Internal Development Draft

Versi dokumen: 1.0  
Status: Draft internal untuk integrasi frontend  
Disusun untuk tahap development SIMPB Laravel API

---

## 1. Base URL

Development local:

```text
http://127.0.0.1:8000/api
```

Semua endpoint API berada di prefix:

```text
/api
```

Contoh endpoint lengkap:

```text
http://127.0.0.1:8000/api/catalog/search?q=Brawijaya
```

---

## 2. Authentication

API menggunakan Laravel Sanctum Personal Access Token.

Header untuk endpoint yang membutuhkan login:

```http
Authorization: Bearer {access_token}
Accept: application/json
```

Login dilakukan melalui:

```http
POST /api/auth/login
```

### 2.1 Login Request

```http
POST /api/auth/login
Content-Type: application/json
Accept: application/json
```

```json
{
  "email": "member@simpb.test",
  "password": "password",
  "device_name": "frontend-dev"
}
```

### 2.2 Login Response

```json
{
  "message": "Login berhasil.",
  "token_type": "Bearer",
  "access_token": "1|token",
  "user": {
    "id": 4,
    "name": "Member SIMPB",
    "username": "member",
    "email": "member@simpb.test",
    "user_type": "member",
    "unit": null,
    "status": "active",
    "member_number": "MBR-0001",
    "member_category": "general",
    "membership_status": "active",
    "member_active_until": "2026-12-31",
    "roles": ["member"],
    "permissions": []
  }
}
```

---

## 3. Standard JSON Error Response

Semua error pada endpoint `/api/*` menggunakan format umum:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Data yang diberikan tidak valid.",
    "details": {
      "email": ["The email field must be a valid email address."]
    }
  },
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-05-20T00:00:00+00:00"
  }
}
```

Kode error utama:

| HTTP Status | error.code | Keterangan |
|---:|---|---|
| 401 | UNAUTHENTICATED | Token tidak ada/tidak valid |
| 403 | FORBIDDEN | Role tidak memiliki akses |
| 404 | RESOURCE_NOT_FOUND | Resource tidak ditemukan |
| 404 | ENDPOINT_NOT_FOUND | Endpoint API tidak ditemukan |
| 405 | METHOD_NOT_ALLOWED | HTTP method salah |
| 422 | VALIDATION_ERROR | Validasi input gagal |
| 422 | BUSINESS_RULE_VIOLATION | Melanggar aturan bisnis |
| 429 | RATE_LIMIT_EXCEEDED | Terlalu banyak request |
| 500 | INTERNAL_SERVER_ERROR | Error server |

Catatan frontend:

- Selalu baca `error.code` untuk menentukan tindakan UI.
- Tampilkan `error.message` sebagai pesan utama.
- Untuk validasi form, baca `error.details`.
- Jangan mengandalkan HTML error page karena API sudah memakai JSON error response.

---

## 4. Role Access Summary

| Role | Akses Umum |
|---|---|
| Guest | Search katalog publik dan detail koleksi public |
| Member | Katalog public/member, pinjaman, reservasi, bookmark, dashboard member |
| Pustakawan | Koleksi library, sirkulasi, reservasi, upload asset library |
| Kurator | Koleksi museum, metadata museum, condition report, upload asset museum |
| Admin | Semua unit dan semua endpoint staff |

Unit scope:

| Role | Unit Scope |
|---|---|
| admin | library + museum |
| pustakawan | library saja |
| kurator | museum saja |
| member | hanya data miliknya sendiri |

---

## 5. Rate Limit

| Endpoint | Limiter | Limit |
|---|---|---:|
| `POST /api/auth/login` | `api-login` | 5 request / menit / email + IP |
| `GET /api/catalog/search` | `api-search` | 60 request / menit / user/IP |
| `POST /api/staff/collections/{identifier}/digital-assets/upload` | `api-upload` | 10 request / menit / user/IP |

Jika limit tercapai, response:

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Terlalu banyak request. Silakan coba lagi nanti."
  },
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-05-20T00:00:00+00:00"
  }
}
```

---

# 6. Endpoint Reference

## 6.1 Auth

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| POST | `/auth/login` | No | Public | Login dan membuat token |
| GET | `/auth/me` | Yes | All authenticated | Data user login |
| POST | `/auth/logout` | Yes | All authenticated | Hapus token aktif |

### 6.1.1 Get Me

```http
GET /api/auth/me
Authorization: Bearer TOKEN
Accept: application/json
```

### 6.1.2 Logout

```http
POST /api/auth/logout
Authorization: Bearer TOKEN
Accept: application/json
```

---

## 6.2 Dashboard

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| GET | `/dashboard` | Yes | admin/pustakawan/kurator/member | Dashboard sesuai role |

Dashboard otomatis menyesuaikan isi response berdasarkan role user login.

Role dashboard:

| Role | Isi Ringkas |
|---|---|
| admin | total koleksi, user, sirkulasi, museum, digital asset, search activity |
| pustakawan | koleksi library, copy, sirkulasi, reservasi, latest library activity |
| kurator | koleksi museum, museum items, condition report, digital asset museum |
| member | membership, pinjaman, denda, reservasi, bookmark |

Contoh:

```http
GET /api/dashboard
Authorization: Bearer TOKEN
Accept: application/json
```

---

## 6.3 Public Catalog

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| GET | `/catalog/search` | Optional | Public/member/staff | Search katalog |
| GET | `/catalog/collections/{identifier}` | Optional | Public/member/staff | Detail koleksi |
| GET | `/catalog/categories` | Optional | Public | Daftar kategori |
| GET | `/catalog/categories/{slug}` | Optional | Public | Detail kategori dan koleksi |

`identifier` bisa berupa:

```text
id, ulid, atau record_code
```

Untuk frontend, rekomendasi utama adalah `record_code`.

### 6.3.1 Search Query Parameters

| Parameter | Contoh | Keterangan |
|---|---|---|
| `q` | `Brawijaya` | Keyword pencarian |
| `unit_type` | `library`, `museum` | Filter unit |
| `collection_type` | `book`, `artifact` | Tipe koleksi |
| `category_id` | `1` | ID kategori |
| `category_slug` | `buku` | Slug kategori |
| `year_from` | `1940` | Tahun awal |
| `year_to` | `1965` | Tahun akhir |
| `subject` | `Metadata` | Filter subject |
| `subject_id` | `1` | Filter subject ID |
| `creator` | `Nugroho` | Filter creator |
| `creator_id` | `1` | Filter creator ID |
| `visibility` | `public` | Filter visibility |
| `publication_status` | `published` | Filter status |
| `is_featured` | `true` | Filter featured |
| `language_code` | `ind` | Filter bahasa |
| `sort` | `relevance`, `latest`, `oldest`, `title_asc`, `title_desc`, `year_asc`, `year_desc`, `featured` | Sorting |
| `per_page` | `10` | Jumlah data per halaman |

Contoh:

```http
GET /api/catalog/search?q=Brawijaya&sort=relevance&per_page=10
Accept: application/json
```

### 6.3.2 Search Response Ringkas

```json
{
  "data": [
    {
      "id": 1,
      "ulid": "01...",
      "record_code": "LIB-BK-2024-0001",
      "unit_type": "library",
      "collection_type": "book",
      "title": "Sejarah Perjuangan Brawijaya",
      "visibility": "public",
      "publication_status": "published",
      "category": {
        "id": 1,
        "name": "Buku",
        "slug": "buku"
      },
      "creators": [],
      "subjects": [],
      "primary_asset": null,
      "library": {},
      "museum": null
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 10,
    "to": 1,
    "total": 1
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": null
  }
}
```

### 6.3.3 Detail Collection

```http
GET /api/catalog/collections/LIB-BK-2024-0001
Accept: application/json
```

Detail library berisi:

- data koleksi umum,
- kategori,
- lokasi,
- creator,
- subject,
- metadata,
- digital assets,
- `library`,
- `library.copies`.

Detail museum berisi:

- data koleksi umum,
- kategori,
- lokasi,
- creator,
- subject,
- metadata,
- digital assets,
- `museum`,
- `museum.materials`,
- `museum.condition_reports`.

---

## 6.4 Member Borrowings

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| GET | `/member/borrowings/active` | Yes | member | Pinjaman aktif |
| GET | `/member/borrowings/history` | Yes | member | Riwayat pinjaman |

### Active Borrowings

```http
GET /api/member/borrowings/active
Authorization: Bearer TOKEN
Accept: application/json
```

### Borrowing History

```http
GET /api/member/borrowings/history?per_page=10
Authorization: Bearer TOKEN
Accept: application/json
```

---

## 6.5 Member Reservations

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| GET | `/member/reservations` | Yes | member | Daftar reservasi member |
| POST | `/member/reservations` | Yes | member | Membuat reservasi |
| POST | `/member/reservations/{reservation}/cancel` | Yes | member/admin/pustakawan | Membatalkan reservasi |

### 6.5.1 Create Reservation Request

```http
POST /api/member/reservations
Authorization: Bearer TOKEN
Content-Type: application/json
Accept: application/json
```

```json
{
  "record_code": "LIB-JRN-2024-0001",
  "notes": "Reservasi untuk dibaca minggu depan"
}
```

Bisa juga memakai:

```json
{
  "collection_id": 1,
  "notes": "Reservasi untuk dibaca minggu depan"
}
```

### 6.5.2 Reservation Business Rules

- Hanya member aktif.
- Koleksi harus `unit_type = library`.
- Koleksi harus `publication_status = published`.
- Koleksi harus `visibility = public/member`.
- Member tidak boleh punya reservasi aktif/notified ganda untuk koleksi yang sama.
- Member dengan denda belum lunas tidak boleh membuat reservasi.
- `queue_position` dihitung otomatis.

### 6.5.3 Cancel Reservation Request

```http
POST /api/member/reservations/{reservation}/cancel
Authorization: Bearer TOKEN
Content-Type: application/json
Accept: application/json
```

```json
{
  "reason": "Tidak jadi meminjam"
}
```

---

## 6.6 Circulation Reservation Staff

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| POST | `/circulation/reservations/{reservation}/allocate-copy` | Yes | admin/pustakawan | Alokasi copy |
| PATCH | `/circulation/reservations/{reservation}/status` | Yes | admin/pustakawan | Ubah status reservasi |

### Allocate Copy Request

```json
{
  "barcode": "LIB-JRN-2024-0001-C1"
}
```

Atau:

```json
{
  "library_copy_id": 1
}
```

### Update Reservation Status Request

```json
{
  "status": "expired",
  "reason": "Reservasi melewati batas pengambilan"
}
```

Allowed status:

```text
active, notified, fulfilled, cancelled, expired
```

---

## 6.7 Member Bookmarks

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| GET | `/member/bookmarks` | Yes | member | Daftar bookmark |
| POST | `/member/bookmarks` | Yes | member | Tambah/simpan bookmark |
| PATCH | `/member/bookmarks/{identifier}` | Yes | member | Update folder/catatan |
| DELETE | `/member/bookmarks/{identifier}` | Yes | member | Hapus bookmark |

### Create Bookmark Request

```json
{
  "record_code": "LIB-BK-2024-0001",
  "folder_name": "Koleksi favorit",
  "notes": "Bahan bacaan sejarah"
}
```

### Update Bookmark Request

```json
{
  "folder_name": "Bahan bacaan sejarah",
  "notes": "Catatan diperbarui"
}
```

Bookmark rules:

- Hanya member.
- Koleksi harus `publication_status = published`.
- Koleksi harus `visibility = public/member`.
- Jika bookmark sudah ada, request `POST` akan update folder/catatan.
- Delete bookmark hanya menghapus relasi, bukan koleksi.

---

## 6.8 Staff Collection Management

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| GET | `/staff/collections` | Yes | admin/pustakawan/kurator | List koleksi internal |
| GET | `/staff/collections/{identifier}` | Yes | admin/pustakawan/kurator | Detail internal |
| POST | `/staff/collections/{identifier}/publish` | Yes | admin/pustakawan/kurator | Publish koleksi |
| POST | `/staff/collections/{identifier}/archive` | Yes | admin/pustakawan/kurator | Archive/soft delete koleksi |
| POST | `/staff/collections/{identifier}/restore` | Yes | admin/pustakawan/kurator | Restore koleksi |
| GET | `/staff/collections/{identifier}/versions` | Yes | admin/pustakawan/kurator | Riwayat versi |
| GET | `/staff/collections/{identifier}/audit-logs` | Yes | admin/pustakawan/kurator | Audit log koleksi |

Unit scope:

- Pustakawan hanya bisa akses `library`.
- Kurator hanya bisa akses `museum`.
- Admin bisa semua.

### Staff Collection List Query

| Parameter | Contoh | Keterangan |
|---|---|---|
| `q` | `Brawijaya` | Keyword |
| `unit_type` | `library` | Unit |
| `collection_type` | `book` | Tipe |
| `publication_status` | `draft` | Status |
| `visibility` | `internal` | Visibility |
| `category_id` | `1` | Kategori |
| `include_trashed` | `true` | Termasuk soft deleted |
| `per_page` | `10` | Pagination |

### Publish Request

```json
{
  "reason": "Metadata sudah diverifikasi"
}
```

### Archive Request

```json
{
  "reason": "Data duplikat dan perlu diarsipkan"
}
```

### Restore Request

```json
{
  "reason": "Dipulihkan setelah verifikasi ulang"
}
```

---

## 6.9 Staff Create Collection

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| POST | `/staff/collections/library` | Yes | admin/pustakawan | Create koleksi perpustakaan |
| POST | `/staff/collections/museum` | Yes | admin/kurator | Create koleksi museum |

Create collection memakai service transaksional:

- `LibraryCollectionService`
- `MuseumCollectionService`

### Library Create Payload Structure

```json
{
  "reason": "Create via staff API",
  "collection": {},
  "library_item": {},
  "copies": [],
  "creators": [],
  "subjects": [],
  "metadata": [],
  "digital_assets": []
}
```

### Museum Create Payload Structure

```json
{
  "reason": "Create via staff API",
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

Response create berisi ringkasan:

```json
{
  "message": "Koleksi perpustakaan berhasil dibuat.",
  "data": {
    "record_code": "API-LIB-20260520120000",
    "unit_type": "library",
    "metadata_count": 25,
    "digital_assets_count": 1,
    "versions_count": 2
  }
}
```

---

## 6.10 Staff Metadata Maintenance

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| PATCH | `/staff/collections/{identifier}/metadata` | Yes | admin/pustakawan/kurator | Upsert metadata |
| DELETE | `/staff/collections/{identifier}/metadata/{metadata}` | Yes | admin/pustakawan/kurator | Hapus metadata |
| POST | `/staff/collections/{identifier}/creators` | Yes | admin/pustakawan/kurator | Tambah/update creator |
| DELETE | `/staff/collections/{identifier}/creators/{creator}` | Yes | admin/pustakawan/kurator | Hapus creator |
| POST | `/staff/collections/{identifier}/subjects` | Yes | admin/pustakawan/kurator | Tambah/update subject |
| DELETE | `/staff/collections/{identifier}/subjects/{subject}` | Yes | admin/pustakawan/kurator | Hapus subject |
| POST | `/staff/collections/{identifier}/digital-assets` | Yes | admin/pustakawan/kurator | Register metadata digital asset |

Unit scope tetap berlaku:

- Pustakawan hanya untuk koleksi library.
- Kurator hanya untuk koleksi museum.
- Admin untuk semua koleksi.

### Upsert Metadata Request

```json
{
  "reason": "Update metadata Dublin Core",
  "metadata": [
    {
      "element_key": "dc.relation",
      "value": "Relasi uji",
      "value_column": "value_string",
      "sort_order": 99,
      "source": "staff_api"
    }
  ]
}
```

Supported value column:

```text
value_string
value_text
value_integer
value_decimal
value_date
value_datetime
value_json
```

### Attach Creator Request

```json
{
  "reason": "Tambah editor",
  "name": "Editor API",
  "role": "editor",
  "is_primary": false,
  "sort_order": 2,
  "authority_source": "LOCAL"
}
```

### Attach Subject Request

```json
{
  "reason": "Tambah subject",
  "term": "Perpustakaan Digital",
  "vocabulary_source": "LOCAL",
  "type": "topic",
  "subject_type": "secondary",
  "sort_order": 2
}
```

### Register Digital Asset Metadata Request

```json
{
  "reason": "Register asset metadata",
  "asset_type": "cover",
  "file_role": "access",
  "disk": "public",
  "path": "library/covers/example.jpg",
  "thumbnail_path": "library/thumbs/example.jpg",
  "mime_type": "image/jpeg",
  "extension": "jpg",
  "size_bytes": 100000,
  "caption": "Cover koleksi",
  "is_primary": false,
  "is_public": false,
  "access_level": "internal",
  "sort_order": 1
}
```

---

## 6.11 Digital Asset Upload and Download

| Method | Endpoint | Auth | Role | Fungsi |
|---|---|---|---|---|
| POST | `/staff/collections/{identifier}/digital-assets/upload` | Yes | admin/pustakawan/kurator | Upload file fisik |
| GET | `/digital-assets/{asset}/download` | Yes | sesuai access_level | Download file |

### Upload File Request

Request menggunakan `multipart/form-data`.

Field wajib:

| Field | Keterangan |
|---|---|
| `file` | File upload |
| `asset_type` | cover/photo/document/pdf/epub/video/audio/thumbnail/mets_package |

Field umum:

| Field | Keterangan |
|---|---|
| `file_role` | original/access/thumbnail/watermarked/derivative |
| `disk` | default public |
| `caption` | caption asset |
| `is_public` | boolean |
| `access_level` | public/member/internal/restricted |
| `sort_order` | urutan tampil |

Contoh curl:

```bash
curl -X POST "http://127.0.0.1:8000/api/staff/collections/LIB-BK-2024-0001/digital-assets/upload" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -F "file=@tmp-upload-test.png;type=image/png" \
  -F "asset_type=cover" \
  -F "file_role=access" \
  -F "disk=public" \
  -F "caption=Cover uji" \
  -F "is_public=0" \
  -F "access_level=internal"
```

Validasi MIME utama:

| asset_type | MIME diterima |
|---|---|
| cover/photo | image/jpeg, image/png, image/webp, image/gif |
| thumbnail | image/jpeg, image/png, image/webp, image/gif, image/svg+xml |
| pdf | application/pdf |
| document | application/pdf, text/plain, doc, docx |
| audio | audio/mpeg, audio/wav, audio/ogg |
| video | video/mp4, video/quicktime, video/x-msvideo |
| mets_package | application/xml, text/xml, application/zip |

Access level:

| access_level | Akses |
|---|---|
| public | Bisa diakses jika `is_public = true` |
| member | Member aktif atau staff sesuai unit |
| internal | Staff sesuai unit |
| restricted | Admin saja |

Catatan thumbnail:

- Saat ini upload gambar membuat thumbnail placeholder SVG.
- Real thumbnail generation belum dibuat.
- Placeholder path disimpan ke `thumbnail_path`.
- Metadata placeholder disimpan ke `technical_metadata.thumbnail`.

---

# 7. Pagination Format

Endpoint list menggunakan format:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 10,
    "to": 10,
    "total": 20
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

Frontend wajib membaca pagination dari `meta` dan `links`, bukan menghitung manual dari jumlah `data`.

---

# 8. Identifier Convention

Banyak endpoint menerima `{identifier}`.

Identifier bisa berupa:

```text
id
ulid
record_code
```

Untuk frontend, rekomendasi utama:

```text
record_code
```

karena lebih mudah dibaca manusia dan cocok untuk staf.

---

# 9. Metadata Standards

SIMPB memakai metadata hybrid:

| Unit | Standar Metadata |
|---|---|
| Perpustakaan | Dublin Core, MARC21 |
| Museum | Dublin Core, CDWA Lite, VRA Core, ISAD(G) |
| Digital Asset | Technical metadata dalam JSON: PREMIS/MIX placeholder sesuai kebutuhan |

Pola penyimpanan metadata:

- Elemen metadata didefinisikan di `metadata_elements`.
- Nilai metadata disimpan di `item_metadata`.
- Repeatable metadata memakai beberapa row dengan `sort_order`.
- Nilai fleksibel memakai kolom typed:
  - `value_string`
  - `value_text`
  - `value_integer`
  - `value_decimal`
  - `value_date`
  - `value_datetime`
  - `value_json`

---

# 10. Audit and Versioning

Aksi penting mencatat audit log dan/atau collection version.

| Aksi | Audit Event |
|---|---|
| Create library collection | `library_collection.created` |
| Create museum collection | `museum_collection.created` |
| Metadata update | `metadata.upserted` |
| Metadata delete | `metadata.deleted` |
| Creator attach/detach | `creator.attached`, `creator.detached` |
| Subject attach/detach | `subject.attached`, `subject.detached` |
| Digital asset register/upload | `digital_asset.registered` |
| Bookmark create/update/delete | `bookmark.created`, `bookmark.updated`, `bookmark.deleted` |
| Reservation create/cancel/status | `reservation.created`, `reservation.cancelled`, `reservation.status_changed` |
| Circulation borrow/return/renew/fine | `circulation.borrowed`, `circulation.returned`, `circulation.renewed`, `circulation.fine_paid` |

Collection version bisa dicek melalui:

```http
GET /api/staff/collections/{identifier}/versions
```

Audit log koleksi bisa dicek melalui:

```http
GET /api/staff/collections/{identifier}/audit-logs
```

---

# 11. Frontend Readiness Checklist

## 11.1 Authentication

- [ ] Frontend bisa login via `POST /api/auth/login`.
- [ ] Token disimpan aman di client.
- [ ] Semua request protected mengirim `Authorization: Bearer TOKEN`.
- [ ] Semua request API mengirim `Accept: application/json`.
- [ ] Logout menghapus token server dan client.
- [ ] Jika menerima `401`, frontend redirect ke login.

## 11.2 Role-Based UI

- [ ] Admin melihat menu semua unit.
- [ ] Pustakawan hanya melihat menu library/sirkulasi.
- [ ] Kurator hanya melihat menu museum.
- [ ] Member hanya melihat dashboard member, pinjaman, reservasi, bookmark.
- [ ] Guest hanya melihat katalog publik.

## 11.3 Error Handling

- [ ] Frontend membaca `error.code`.
- [ ] Frontend membaca `error.message`.
- [ ] Untuk validasi, frontend membaca `error.details`.
- [ ] `429 RATE_LIMIT_EXCEEDED` ditampilkan sebagai pesan tunggu/coba lagi.
- [ ] `403 FORBIDDEN` ditampilkan sebagai akses ditolak.
- [ ] `422 VALIDATION_ERROR` ditampilkan di form.
- [ ] `422 BUSINESS_RULE_VIOLATION` ditampilkan sebagai notifikasi aturan bisnis.

## 11.4 Catalog

- [ ] Search mendukung query `q`.
- [ ] Filter unit, type, kategori, tahun, creator, subject.
- [ ] Pagination memakai `meta` dan `links`.
- [ ] Detail library menampilkan bibliographic metadata dan copy.
- [ ] Detail museum menampilkan object metadata, material, condition report.
- [ ] Detail asset memakai kebijakan access level.

## 11.5 Member Area

- [ ] Member bisa melihat pinjaman aktif.
- [ ] Member bisa melihat riwayat pinjam.
- [ ] Member bisa melihat reservasi.
- [ ] Member bisa membuat dan membatalkan reservasi.
- [ ] Member bisa CRUD bookmark.
- [ ] Member dashboard menampilkan status membership dan denda.

## 11.6 Staff Area

- [ ] Staff bisa melihat list internal.
- [ ] Staff bisa melihat detail internal.
- [ ] Staff bisa publish/archive/restore koleksi.
- [ ] Staff bisa create koleksi library/museum sesuai role.
- [ ] Staff bisa update metadata.
- [ ] Staff bisa tambah/hapus creator dan subject.
- [ ] Staff bisa register/upload digital asset.
- [ ] Staff bisa melihat audit log dan version history.
- [ ] Staff UI menghormati unit scope: pustakawan library, kurator museum.

## 11.7 Upload and Storage

- [ ] `php artisan storage:link` sudah berjalan.
- [ ] File upload memakai `multipart/form-data`.
- [ ] Frontend mengirim `Accept: application/json`.
- [ ] Upload menampilkan progress.
- [ ] MIME error `422` ditampilkan jelas.
- [ ] File internal/restricted tidak ditampilkan sebagai public URL langsung.
- [ ] Download file memakai endpoint `/api/digital-assets/{asset}/download`.

## 11.8 Production Preparation

- [ ] `APP_DEBUG=false`.
- [ ] `APP_URL` sesuai domain produksi.
- [ ] Database production terpisah dari development.
- [ ] Rate limit sudah sesuai kebutuhan production.
- [ ] CORS disesuaikan domain frontend.
- [ ] Storage disk production ditentukan.
- [ ] Backup database dan storage disiapkan.
- [ ] Logging dan monitoring disiapkan.
- [ ] Seeder demo tidak dijalankan di production.

---

# 12. Minimal Smoke Test Before Frontend

Jalankan sebelum mulai integrasi frontend:

```bash
php artisan optimize:clear
php artisan route:list --path=api
php artisan about
```

Test API minimum:

1. Login member.
2. Login pustakawan.
3. Login kurator.
4. Login admin.
5. Search katalog.
6. Detail koleksi library.
7. Detail koleksi museum.
8. Dashboard sesuai role.
9. Create bookmark.
10. Create reservasi.
11. Staff list internal.
12. Staff detail internal.
13. Upload digital asset test.
14. Error validation `422`.
15. Error unauthenticated `401`.
16. Error forbidden `403`.
17. Error rate limit `429`.

---

# 13. Route Snapshot

Untuk menyimpan snapshot route aktual:

```bash
php artisan route:list --path=api > docs\routes-api-current.txt
```

File `routes-api-current.txt` sebaiknya diperbarui setiap ada perubahan route.

---

# 14. Known Limitations

Dokumentasi ini adalah draft internal development, bukan OpenAPI/Swagger final.

Belum termasuk:

- endpoint update detail bibliografi `library_items`,
- endpoint update detail museum `museum_items`,
- endpoint delete digital asset,
- endpoint generate thumbnail asli,
- endpoint export/import metadata,
- endpoint laporan statistik detail,
- endpoint frontend-specific view model,
- endpoint CRUD kategori/lokasi/reference data,
- endpoint manajemen user dari UI admin.

Item tersebut dapat direncanakan setelah frontend baseline berjalan.
