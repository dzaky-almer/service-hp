# Sistem Manajemen Servis Barang

Aplikasi web untuk bengkel servis perangkat (HP, laptop, tablet, konsol, dll).
Mengelola pelanggan, penerimaan servis, diagnosis, estimasi biaya, pengerjaan,
pembayaran, serah terima, laporan, dan audit — dengan pembatasan akses per peran.

Dokumen acuan ada di `rules/` (PRD, ARCHITECTURE, SECURITY, TASK, TODO, AGENT, SKILLS)
dan sistem visual di [`DESIGN.md`](DESIGN.md).

## Peran dan hak akses

| Peran | Bisa |
|---|---|
| **Owner** | Semua fitur + kelola staf, pengaturan bisnis, tarif jasa, audit log, laporan seluruh rentang |
| **Admin** | Servis, pelanggan, spare part, pembayaran, serah terima, laporan (pendapatan dibatasi hari berjalan) |
| **Teknisi** | Pekerjaan yang ditugaskan: diagnosis, pengerjaan, pemakaian spare part, QC, dashboard & laporan sendiri |
| **Customer** | Melacak servis miliknya, menyetujui/menolak estimasi, membayar via gateway, dashboard sendiri |

Akun Admin/Teknisi hanya dibuat lewat undangan Owner; akun Owner lewat `php artisan owner:buat`.
Registrasi publik selalu menghasilkan akun Customer.

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_mysql`, `gd`/`fileinfo`, `zip`
- Composer 2
- MySQL 8 (atau MariaDB setara)
- Node.js 20 + npm (hanya untuk build aset Tailwind/Alpine)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate

# isi DB_* di .env, lalu:
php artisan migrate --seed
npm install && npm run build

php artisan owner:buat            # atau: php artisan owner:buat --nama=... --email=... --password=...
php artisan serve
```

`.env` wajib memuat `GATEWAY_WEBHOOK_SECRET` (rahasia HMAC webhook pembayaran).
Tanpa nilai, aplikasi memakai default lokal `simulasi-rahasia-lokal` — **ganti sebelum produksi**.

## Data contoh (demo)

```bash
php artisan db:seed --class=DemoSeeder
```

Membuat tiga akun (`admin@demo.test`, `teknisi@demo.test`, `customer@demo.test`, password
`rahasia123`), satu pelanggan, tiga servis pada status berbeda (satu lunas & siap diambil,
satu dikerjakan dan lewat SLA), serta spare part dengan stok awal.
Seeder berhenti sendiri bila tabel `servis` sudah berisi data. **Jangan dipakai di produksi.**

## Perintah artisan

| Perintah | Fungsi |
|---|---|
| `owner:buat` | Buat akun Owner (satu-satunya jalur membuat Owner) |
| `pembayaran:kedaluwarsa` | Tandai pembayaran gateway yang melewati 24 jam sebagai gagal |
| `db:seed --class=DemoSeeder` | Isi data demo |

Jalankan `pembayaran:kedaluwarsa` berkala (cron tiap jam) di produksi.

## Pengujian

```bash
php artisan test          # atau: ./vendor/bin/pest
./vendor/bin/pint --test  # gaya kode
```

Suite mencakup aturan peran, isolasi data antar pelanggan, transisi status, snapshot harga,
penguncian stok, webhook HMAC, serah terima, dan laporan. Peta bukti tiap butir checklist
keamanan ada di `rules/SECURITY.md` §9.

## Struktur singkat

```
app/
  Http/Controllers/{Admin,Teknisi,Pelanggan,Webhook}/   controller per peran
  Http/Requests/                                        validasi + pesan Indonesia
  Models/                                               entitas + aturan status
  Services/                                             logika bisnis (satu tempat ubah status)
  Policies/                                             izin per objek
  Support/{Menu,format}.php                             menu per peran, helper rupiah/tanggal
database/migrations/                                    skema; tabel append-only untuk audit/stok/pembayaran
resources/views/{admin,teknisi,pelanggan,servis,cetak,laporan}/
tests/Feature/                                          test per domain
rules/                                                  spesifikasi produk, arsitektur, keamanan
```

## Catatan operasional

- Foto servis disimpan di disk privat `local` dan disajikan lewat route ber-otorisasi.
- `audit_log`, `webhook_log`, `pembayaran`, dan `mutasi_stok` hanya bertambah (INSERT);
  koreksi dilakukan dengan baris baru (pembalikan / penyesuaian), bukan mengubah baris lama.
- Semua perubahan `servis.status` lewat `ServisService::pindahStatus()` sehingga riwayat dan
  audit selalu tercatat.
- Nomor servis berurutan per tahun (`SRV-YYYY-NNNNN`) dan kode tracking acak 12 karakter.

## Lisensi

MIT.