# Aplikasi Survei Kepuasan Pasien RSUD RAA Soewondo Pati

Aplikasi web untuk survei kepuasan pasien rawat inap dengan fitur admin dashboard lengkap, manajemen user, manajemen pertanyaan, laporan statistik, dan export data.

## 📋 Fitur Utama

### Untuk Pasien (Frontend)
- ✅ Akses survei tanpa login melalui link
- ✅ Input Nomor Rekam Medis (NOMR) - wajib
- ✅ Rating bintang interaktif (1-5) untuk setiap pertanyaan
- ✅ Kolom saran opsional
- ✅ Validasi form dan submit via AJAX
- ✅ Tampilan responsif dengan Tailwind CSS

### Untuk Admin (Backend)
- ✅ Login admin dengan autentikasi
- ✅ Dashboard dengan statistik real-time
- ✅ Data survei dengan detail per responden
- ✅ Laporan statistik dengan grafik Chart.js
- ✅ **Manajemen User Admin** - Tambah, edit, hapus admin
- ✅ **Manajemen Pertanyaan** - Kelola pertanyaan survei
- ✅ Export data ke Excel dan PDF
- ✅ Interface yang user-friendly dengan DataTables

## 🛠️ Instalasi

### Persyaratan Sistem
- XAMPP/WAMP/LAMP (PHP 7.4+ dan MySQL 5.7+)
- Web browser modern
- Koneksi internet (untuk CDN Tailwind CSS dan Chart.js)

### Langkah Instalasi

1. **Download dan Extract**
   ```bash
   # Extract file ke direktori htdocs XAMPP
   C:\xampp\htdocs\survei_kepuasan\
   ```

2. **Setup Database**
   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Import file `database.sql`
   - Database `survei_kepuasan` akan dibuat otomatis dengan data sample

3. **Konfigurasi Database**
   - Edit file `config/database.php` jika perlu
   - Default: host=localhost, user=root, password=(kosong)

4. **Set Permissions**
   - Pastikan folder aplikasi dapat diakses web server
   - Untuk Linux: `chmod 755 -R /path/to/survei_kepuasan`

5. **Akses Aplikasi**
   - Survei Pasien: `http://localhost/survei_kepuasan/`
   - Admin Panel: `http://localhost/survei_kepuasan/admin/`
   - Manajemen User: `http://localhost/survei_kepuasan/admin/manajemen_user.php`
   - Manajemen Pertanyaan: `http://localhost/survei_kepuasan/admin/manajemen_pertanyaan.php`

## 🔐 Login Admin Default

```
Username: admin
Password: admin123
```

⚠️ **Penting**: Ubah password default setelah instalasi!

## 📊 Struktur Database

### Tabel `questions`
Menyimpan daftar pertanyaan survei
- `id` - Primary key
- `question_text` - Teks pertanyaan
- `is_active` - Status aktif pertanyaan
- `created_at` - Timestamp

### Tabel `survey_responses`
Menyimpan data pasien dan saran
- `id` - Primary key
- `nomr` - Nomor Rekam Medis
- `saran` - Saran dari pasien
- `created_at` - Timestamp

### Tabel `survey_answers`
Menyimpan jawaban per pertanyaan
- `id` - Primary key
- `response_id` - Foreign key ke survey_responses
- `question_id` - Foreign key ke questions
- `rating` - Rating 1-5

### Tabel `admins`
Menyimpan data admin
- `id` - Primary key
- `username` - Username admin
- `password` - Password (hashed)
- `created_at` - Timestamp

## 🎯 Pertanyaan Default

1. Bagaimana pelayanan perawat selama perawatan?
2. Bagaimana kebersihan ruang rawat inap?
3. Bagaimana keramahan petugas rumah sakit?
4. Bagaimana kenyamanan fasilitas rawat inap?
5. Bagaimana kualitas makanan yang disajikan?
6. Secara keseluruhan, apakah Anda puas dengan pelayanan RS Soewondo?

## 📱 Teknologi yang Digunakan

- **Backend**: PHP Native (7.4+)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Tailwind CSS
- **Charts**: Chart.js
- **Tables**: DataTables
- **Icons**: Font Awesome

## 🔒 Fitur Keamanan

- ✅ CSRF Protection
- ✅ Input sanitization
- ✅ SQL injection protection (PDO)
- ✅ Session management
- ✅ Password hashing
- ✅ Access control untuk admin area

## 📈 Fitur Laporan

### Dashboard Admin
- Total responden
- Rating rata-rata
- Tingkat kepuasan (%)
- Grafik distribusi rating
- Tren kepuasan 7 hari terakhir

### Laporan Detail
- Statistik per pertanyaan
- Grafik bar rating rata-rata
- Grafik pie distribusi kepuasan
- Export Excel (.xls)
- Export PDF

## 🚀 Penggunaan

### Untuk Pasien
1. Buka link survei
2. Masukkan Nomor Rekam Medis
3. Berikan rating untuk setiap pertanyaan
4. Tulis saran (opsional)
5. Submit survei

### Untuk Admin
1. Login ke admin panel
2. Lihat dashboard untuk overview
3. Akses "Data Survei" untuk detail respons
4. Buka "Laporan" untuk analisis statistik
5. Export data sesuai kebutuhan

## 🔧 Kustomisasi

### Menambah/Edit Pertanyaan
1. Login sebagai admin
2. Edit database tabel `questions`
3. Set `is_active = 1` untuk mengaktifkan

### Mengubah Tampilan
- Edit file CSS di bagian `<style>` pada setiap halaman
- Modifikasi kelas Tailwind CSS sesuai kebutuhan
- Customize warna tema di variabel CSS

### Backup Data
```sql
mysqldump -u root -p survei_kepuasan > backup_survei.sql
```

## 📞 Support

Untuk bantuan teknis atau pertanyaan:
- 📧 Email: admin@rsudsoewondo.go.id
- 📱 WhatsApp: +62-xxx-xxxx-xxxx

## 📄 Lisensi

© 2024 RSUD Soewondo. Semua hak dilindungi.

---

**Dibuat untuk RSUD RAA Soewondo Pati** 🏥
*Sistem Survei Kepuasan Pasien Rawat Inap*
