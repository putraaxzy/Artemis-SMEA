# API Documentation - Sistem Manajemen Tugas

## 📋 Daftar Isi

-   [Authentication](#authentication)
-   [Tugas Management](#tugas-management)
-   [Bot Reminder](#bot-reminder)
-   [Status Codes](#status-codes)

---

## 🔐 Authentication

### 1. Get Registration Options

```http
GET /api/auth/register-options
```

**Response:**

```json
{
    "berhasil": true,
    "data": {
        "kelas": ["X", "XI", "XII"],
        "jurusan": ["MPLB", "RPL", "PM", "TKJ", "AKL"]
    },
    "pesan": "Opsi registrasi berhasil diambil"
}
```

### 2. Register

```http
POST /api/auth/register
```

**Body:**

```json
{
    "username": "johndoe",
    "name": "John Doe",
    "telepon": "081234567890",
    "password": "password12345",
    "role": "siswa",
    "kelas": "XII",
    "jurusan": "RPL"
}
```

**Validasi:**

-   **Username**: Hanya boleh mengandung huruf (a-z, A-Z), angka (0-9), dan underscore (\_), harus unique, maksimal 255 karakter
-   **Password**: Minimal 8 karakter

**Response:**

```json
{
    "berhasil": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "pengguna": {
            "id": 1,
            "username": "johndoe",
            "name": "John Doe",
            "telepon": "081234567890",
            "role": "siswa",
            "kelas": "XII",
            "jurusan": "RPL",
            "dibuat_pada": "2025-12-01T10:00:00.000000Z",
            "diperbarui_pada": "2025-12-01T10:00:00.000000Z"
        }
    },
    "pesan": "Registrasi berhasil"
}
```

### 3. Login

```http
POST /api/auth/login
```

**Body:**

```json
{
    "username": "johndoe",
    "password": "password12345"
}
```

**Response:**

```json
{
    "berhasil": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "pengguna": {
            "id": 1,
            "username": "johndoe",
            "name": "John Doe",
            "telepon": "081234567890",
            "role": "siswa",
            "kelas": "XII",
            "jurusan": "RPL",
            "dibuat_pada": "2025-12-01T10:00:00.000000Z",
            "diperbarui_pada": "2025-12-01T10:00:00.000000Z"
        }
    },
    "pesan": "Login berhasil"
}
```

### 4. Logout

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

### 5. Get Current User

```http
GET /api/auth/me
Authorization: Bearer {token}
```

---

## 📚 Tugas Management

**PENTING:**

-   **Guru** = Pemberi tugas (membuat & monitoring tugas)
-   **Siswa** = Penerima tugas (mengerjakan & submit tugas)

### 1. Buat Tugas (Guru Only)

```http
POST /api/tugas
Authorization: Bearer {token}
```

**Body (Target Siswa):**

```json
{
    "judul": "Tugas Matematika Bab 1",
    "target": "siswa",
    "id_target": [1, 2, 3, 4],
    "tipe_pengumpulan": "link"
}
```

**Body (Target Kelas):**

```json
{
    "judul": "Tugas Matematika Bab 1",
    "target": "kelas",
    "id_target": [
        { "kelas": "XII", "jurusan": "RPL" },
        { "kelas": "XII", "jurusan": "MPLB" }
    ],
    "tipe_pengumpulan": "langsung"
}
```

**Validasi:**

-   **tipe_pengumpulan**: Required, harus `link` atau `langsung`
    -   `link` = Siswa submit via link Google Drive/online
    -   `langsung` = Siswa kumpulkan langsung ke guru (offline)

**Response:**

```json
{
  "berhasil": true,
  "data": {
    "id": 1,
    "id_guru": 5,
    "judul": "Tugas Matematika Bab 1",
    "target": "kelas",
    "id_target": [...],
    "tipe_pengumpulan": "link",
    "total_siswa": 25,
    "dibuat_pada": "2025-12-01T10:00:00.000000Z",
    "diperbarui_pada": "2025-12-01T10:00:00.000000Z"
  },
  "pesan": "Tugas berhasil dibuat"
}
```

### 2. Ambil Daftar Tugas

```http
GET /api/tugas
Authorization: Bearer {token}
```

**Response (Guru - Tugas yang DIBUAT):**

```json
{
    "berhasil": true,
    "data": [
        {
            "id": 1,
            "judul": "Tugas Matematika Bab 1",
            "target": "kelas",
            "tipe_pengumpulan": "link",
            "total_siswa": 25,
            "pending": 10,
            "dikirim": 12,
            "selesai": 3,
            "dibuat_pada": "2025-12-01T10:00:00.000000Z"
        }
    ],
    "pesan": "Data tugas berhasil diambil"
}
```

**Response (Siswa - Tugas yang DITERIMA):**

```json
{
    "berhasil": true,
    "data": [
        {
            "id": 1,
            "judul": "Tugas Matematika Bab 1",
            "target": "kelas",
            "tipe_pengumpulan": "link",
            "guru": "Pak Budi",
            "status": "pending",
            "dibuat_pada": "2025-12-01T10:00:00.000000Z"
        }
    ],
    "pesan": "Data tugas berhasil diambil"
}
```

### 3. Ambil Detail Tugas (Guru Only)

```http
GET /api/tugas/{id}/detail
Authorization: Bearer {token}
```

**Response:**

```json
{
  "berhasil": true,
  "data": {
    "id": 1,
    "judul": "Tugas Matematika Bab 1",
    "target": "kelas",
    "id_target": [...],
    "tipe_pengumpulan": "link",
    "dibuat_pada": "2025-12-01T10:00:00.000000Z",
    "statistik": {
      "total_siswa": 25,
      "pending": 10,
      "dikirim": 12,
      "selesai": 3,
      "ditolak": 0
    },
    "penugasan": [
      {
        "id": 15,
        "siswa": {
          "id": 3,
          "username": "ahmad123",
          "name": "Ahmad Rizki",
          "telepon": "081234567890",
          "kelas": "XII",
          "jurusan": "RPL"
        },
        "status": "dikirim",
        "link_drive": "https://drive.google.com/...",
        "tanggal_pengumpulan": "2025-12-01T14:30:00.000000Z",
        "dibuat_pada": "2025-12-01T10:00:00.000000Z",
        "diperbarui_pada": "2025-12-01T14:30:00.000000Z"
      }
    ]
  },
  "pesan": "Detail tugas berhasil diambil"
}
```

### 4. Submit Tugas (Siswa Only)

```http
POST /api/tugas/{id}/submit
Authorization: Bearer {token}
```

**Body (untuk tipe_pengumpulan = "link"):**

```json
{
    "link_drive": "https://drive.google.com/file/d/xxxxx"
}
```

**Body (untuk tipe_pengumpulan = "langsung"):**

```json
{}
```

**Note:**
- Jika tugas dengan `tipe_pengumpulan` = `link`, field `link_drive` **wajib** diisi dengan URL valid
- Jika tugas dengan `tipe_pengumpulan` = `langsung`, tidak perlu body atau bisa kosong (siswa konfirmasi sudah kumpul langsung ke guru)

**Response:**

```json
{
    "berhasil": true,
    "data": {
        "id": 15,
        "id_tugas": 1,
        "id_siswa": 3,
        "status": "dikirim",
        "link_drive": "https://drive.google.com/file/d/xxxxx",
        "tanggal_pengumpulan": "2025-12-01T14:30:00.000000Z",
        "dibuat_pada": "2025-12-01T10:00:00.000000Z",
        "diperbarui_pada": "2025-12-01T14:30:00.000000Z"
    },
    "pesan": "Penugasan berhasil diajukan"
}
```

### 4. Ambil Penugasan Pending (Guru Only)

```http
GET /api/tugas/{id}/pending
Authorization: Bearer {token}
```

**Response:**

```json
{
    "berhasil": true,
    "data": [
        {
            "id": 10,
            "id_siswa": 5,
            "siswa": {
                "id": 5,
                "name": "Ahmad",
                "telepon": "081234567890",
                "kelas": "XII",
                "jurusan": "RPL"
            },
            "status": "pending",
            "dibuat_pada": "2025-12-01T10:00:00.000000Z"
        }
    ],
    "pesan": "Data penugasan pending berhasil diambil"
}
```

### 5. Update Status Penugasan (Guru Only)

```http
PUT /api/tugas/penugasan/{id}/status
Authorization: Bearer {token}
```

**Body:**

```json
{
    "status": "selesai"
}
```

**Available Status:** `selesai`, `ditolak`

---

## 🤖 Bot Reminder

### 1. Kirim Reminder ke Siswa Pending (Guru Only)

```http
POST /api/tugas/{id}/reminder
Authorization: Bearer {token}
```

**Response:**

```json
{
    "berhasil": true,
    "data": {
        "total_reminder": 10,
        "reminders": [
            {
                "siswa": "Ahmad",
                "telepon": "081234567890",
                "reminder_id": 1
            }
        ]
    },
    "pesan": "Reminder berhasil dikirim ke 10 siswa"
}
```

### 2. Catat Reminder dari Bot

```http
POST /api/bot/reminder
```

**Body:**

```json
{
    "id_tugas": 1,
    "id_siswa": 5,
    "pesan": "Reminder: Segera kerjakan tugas Matematika",
    "id_pesan": "msg_12345"
}
```

### 3. Ambil History Reminder (Guru Only)

```http
GET /api/bot/reminder/{idTugas}
Authorization: Bearer {token}
```

**Response:**

```json
{
    "berhasil": true,
    "data": [
        {
            "id": 1,
            "siswa": {
                "id": 5,
                "name": "Ahmad",
                "telepon": "081234567890"
            },
            "pesan": "Reminder: Segera kerjakan tugas Matematika",
            "id_pesan": "msg_12345",
            "dibuat_pada": "2025-12-01T15:00:00.000000Z"
        }
    ],
    "pesan": "Data reminder berhasil diambil"
}
```

---

## 📊 Status Codes

| Code | Description                          |
| ---- | ------------------------------------ |
| 200  | Success                              |
| 201  | Created                              |
| 400  | Bad Request (Validation Error)       |
| 401  | Unauthorized (Invalid/Expired Token) |
| 403  | Forbidden (Insufficient Permission)  |
| 404  | Not Found                            |
| 500  | Internal Server Error                |

---

## 🔑 Enum Values

### Role

-   `guru`
-   `siswa`

### Kelas

-   `X`
-   `XI`
-   `XII`

### Jurusan

-   `MPLB`
-   `RPL`
-   `PM`
-   `TKJ`
-   `AKL`

### Tipe Pengumpulan

-   `link` - Siswa submit via link Google Drive/online
-   `langsung` - Siswa kumpulkan langsung ke guru (offline)

### Status Penugasan

-   `pending` - Belum dikerjakan
-   `dikirim` - Sudah dikumpulkan, menunggu review
-   `selesai` - Diterima oleh guru
-   `ditolak` - Ditolak oleh guru

---

## 📝 Notes

1. **Login**: User hanya bisa login menggunakan `username`
2. **Username Requirements**:

    - Hanya boleh huruf (a-z, A-Z), angka (0-9), dan underscore (\_)
    - Harus unique
    - Maksimal 255 karakter
    - Tidak boleh spasi atau karakter spesial lainnya

3. **Password Requirements**:

    - Minimal 8 karakter
    - Tidak ada batasan karakter spesial (bisa menggunakan huruf, angka, simbol)

4. Semua endpoint (kecuali auth dan bot webhook) memerlukan JWT token

5. Token dikirim via header: `Authorization: Bearer {token}`

6. Semua response menggunakan format JSON dengan struktur:

    ```json
    {
      "berhasil": true/false,
      "data": {...},
      "pesan": "pesan sukses/error"
    }
    ```

7. Untuk siswa: `kelas` dan `jurusan` wajib diisi saat registrasi

8. Untuk guru: `kelas` dan `jurusan` bisa dikosongkan (nullable)
