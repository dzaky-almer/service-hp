# DESIGN.md — Sistem visual Sistem Manajemen Servis Barang

Dokumen ini mencatat sistem visual **yang benar-benar dipakai** di `resources/views`.
Sumber kebenaran tetap kode: komponen Blade di `resources/views/components/` dan kelas
Tailwind di tiap halaman. Kalau dokumen dan kode berbeda, kode menang — perbarui dokumen.

## 1. Prinsip

1. **Netral, bukan meriah.** Latar abu muda, kartu putih, satu aksen indigo. Warna dipakai
   untuk status, bukan hiasan.
2. **Padat data.** Halaman kerja (servis, pekerjaan, laporan) mengutamakan tabel yang bisa
   dipindai cepat: satu baris = satu servis, status selalu di kolom yang sama.
3. **Bisa dipakai di HP.** Teknisi membuka halaman dari ponsel di lantai kerja.
4. **Jujur pada status.** Warna dan label status konsisten di seluruh aplikasi.

## 2. Token

| Token | Nilai | Kelas Tailwind |
|---|---|---|
| Font | Figtree 400/500/600 | `font-sans` (`tailwind.config.js`) |
| Latar halaman | abu muda | `bg-gray-100` |
| Permukaan/kartu | putih, sudut 8px, bayangan halus | `bg-white shadow sm:rounded-lg` |
| Garis pemisah | `gray-100` / `gray-200` | `border-b border-gray-100` |
| Teks utama | `gray-800` | `text-gray-800` |
| Teks sekunder | `gray-500` | `text-gray-500` |
| Aksen (tautan aktif, fokus) | indigo | `border-indigo-400`, `focus:ring-indigo-500` |
| Lebar konten | `max-w-7xl` (shell), `max-w-6xl` (halaman data) | `mx-auto px-4 sm:px-6 lg:px-8` |
| Jarak vertikal antar blok | 24px | `space-y-6` |

Tanpa token warna kustom: seluruh palet Tailwind bawaan. Tidak ada CSS kustom di
`resources/css/app.css` selain direktif Tailwind.

## 3. Tipografi

| Peran | Kelas |
|---|---|
| Judul halaman | `text-xl font-semibold text-gray-800 leading-tight` |
| Judul kartu | `font-semibold text-gray-800` |
| Teks bantuan | `text-sm text-gray-500` |
| Label form | `text-sm text-gray-700` (di dalam `<x-input-label>`) |
| Header tabel | `text-xs uppercase text-gray-500` |
| Angka uang | `rupiah()` + `text-right` di kolom tabel |
| Cetak (tanda terima, bukti) | `text-sm`, tabel tanpa bingkai dekoratif |

## 4. Komponen

| Komponen | Berkas | Catatan |
|---|---|---|
| Tombol utama | `components/primary-button` | `bg-gray-800`, teks putih, uppercase, `rounded-md` |
| Tombol sekunder | `components/secondary-button` | putih + garis `gray-300` |
| Tombol bahaya | `components/danger-button` | `bg-red-600` |
| Input teks | `components/text-input` | `rounded-md shadow-sm`, fokus indigo |
| Galat input | `components/input-error` | `text-sm text-red-600` (lewat `$errors->get('field')`) |
| Menu atas | `layouts/navigation` + `components/nav-link` | item dari `App\Support\Menu`, aktif = garis bawah indigo |
| Notifikasi hasil | blok manual per halaman | `bg-green-50 text-green-800` (sukses), `bg-yellow-50` (peringatan) |

Aturan: **setiap** `<input>`/`<select>`/`<textarea>` yang punya aturan validasi diikuti
`<x-input-error :messages="$errors->get('nama_field')" />`.

## 5. Warna status

| Status servis | Warna badge |
|---|---|
| Diterima, Menunggu Diagnosis, Menunggu Persetujuan | abu (`bg-gray-100 text-gray-700`) |
| Disetujui, Dikerjakan, Quality Control | biru (`bg-blue-100 text-blue-800`) |
| Siap Diambil | kuning (`bg-yellow-100 text-yellow-800`) |
| Selesai | hijau (`bg-green-100 text-green-800`) |
| Ditolak, Dibatalkan | merah (`bg-red-100 text-red-800`) |

Warna status pembayaran: Menunggu = kuning, Berhasil = hijau, Gagal/Ditolak = merah.
Status stok: di bawah `stok_minimum` = merah.

Warna tidak pernah menjadi satu-satunya penanda: selalu disertai label teks.

## 6. Tata letak dan responsif

- Shell: `min-h-screen bg-gray-100`, navigasi 64px, konten `max-w-*` dengan padding
  `px-4` → `sm:px-6` → `lg:px-8`.
- Navigasi menyusut jadi menu lipat di bawah `sm` (Alpine `x-data="{ open: false }"`).
- Form panjang: `grid gap-4 sm:grid-cols-2`, aksi `sm:col-span-2`.
- **Semua tabel** dibungkus `<div class="overflow-x-auto">` supaya tidak memaksa lebar
  halaman di ponsel. Halaman cetak (`cetak/`) dikecualikan (kertas A4).
- Kartu ringkasan dashboard: `grid gap-4 sm:grid-cols-2 lg:grid-cols-4`.
- Target sentuh minimal 40px: tombol memakai `px-4 py-2`.

## 7. Aksesibilitas

- Setiap input punya `<label for>` yang benar (komponen `input-label`).
- Fokus keyboard terlihat: `focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2`.
- Kontras: teks utama `gray-800` di atas putih; teks sekunder minimal `gray-500`
  (jangan `gray-400` untuk teks isi).
- Tombol ikon selalu punya teks atau `aria-label`.
- Status penting tidak mengandalkan warna saja (§5).
- Tabel memakai `<thead>`/`<th>` dan angka uang rata kanan agar mudah dibaca pembaca layar.

## 8. Pesan

Satu tempat di `layouts/app.blade.php`, jadi setiap halaman ikut tanpa menambah markup
sendiri:

- **Hasil aksi** (`session('status')`): kotak `bg-green-50 border-green-200 text-green-800`,
  `role="status"`. Halaman tidak lagi menulis blok ini sendiri.
- **Galat validasi** (`$errors` tidak kosong): kotak `bg-red-50 border-red-200 text-red-800`,
  `role="alert"`, judul "Periksa kembali isian berikut:" + daftar pesan. Rincian per isian
  tetap muncul di sebelah input (`x-input-error`).
- Teks pesan dari `lang/id/{validation,auth,passwords}.php` — Bahasa Indonesia, sebut nama
  isian seperti yang terlihat pengguna (`nama`, `no. HP`), bukan nama kolom database.

## 9. Cetak

`resources/views/cetak/tanda-terima.blade.php` dan `cetak/bukti-pengambilan.blade.php`
memakai lembar A4 putih, margin 24px, tanpa navigasi, dan tanpa warna latar (hemat tinta).
Dibuka lewat route PDF (dompdf) — lihat `Admin\SerahTerimaController::bukti()`.