<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service order (fitur 15–22). Satu baris = satu perangkat yang dititipkan.
 *
 * `status` hanya berubah lewat `ServisService::pindahStatus()` (ARCHITECTURE §3.2).
 */
#[Fillable([
    'id_pelanggan',
    'id_teknisi',
    'jenis_perangkat',
    'merek',
    'model',
    'nomor_seri',
    'keluhan',
    'kondisi_fisik',
    'kelengkapan',
    'sla_hari_salinan',
    'biaya_pemeriksaan_salinan',
    'tanggal_masuk',
    'dibuat_oleh',
])]
class Servis extends Model
{
    protected $table = 'servis';

    protected $primaryKey = 'id_servis';

    public $timestamps = false;

    public const DITERIMA = 'diterima';

    public const MENUNGGU_DIAGNOSIS = 'menunggu_diagnosis';

    public const MENUNGGU_PERSETUJUAN = 'menunggu_persetujuan';

    public const DISETUJUI = 'disetujui';

    public const DIKERJAKAN = 'dikerjakan';

    public const QUALITY_CONTROL = 'quality_control';

    public const SIAP_DIAMBIL = 'siap_diambil';

    public const SELESAI = 'selesai';

    public const DITOLAK = 'ditolak';

    public const DIBATALKAN = 'dibatalkan';

    public const LABEL_STATUS = [
        self::DITERIMA => 'Diterima',
        self::MENUNGGU_DIAGNOSIS => 'Menunggu Diagnosis',
        self::MENUNGGU_PERSETUJUAN => 'Menunggu Persetujuan',
        self::DISETUJUI => 'Disetujui',
        self::DIKERJAKAN => 'Dikerjakan',
        self::QUALITY_CONTROL => 'Quality Control',
        self::SIAP_DIAMBIL => 'Siap Diambil',
        self::SELESAI => 'Selesai',
        self::DITOLAK => 'Ditolak',
        self::DIBATALKAN => 'Dibatalkan',
    ];

    /** Status akhir: tidak boleh berubah lagi. */
    public const STATUS_AKHIR = [self::SELESAI, self::DIBATALKAN];

    /** Status yang masih boleh dibatalkan Admin; selebihnya hanya Owner. */
    public const BATAL_OLEH_ADMIN = [self::DITERIMA, self::MENUNGGU_DIAGNOSIS, self::MENUNGGU_PERSETUJUAN];

    public const JENIS_PERANGKAT = [
        'hp' => 'HP',
        'laptop' => 'Laptop',
        'komputer' => 'Komputer',
        'elektronik' => 'Elektronik',
        'lainnya' => 'Lainnya',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
            'biaya_pemeriksaan_salinan' => 'decimal:2',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_teknisi', 'id_pengguna');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatStatusServis::class, 'id_servis', 'id_servis')->orderBy('waktu');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(FotoServis::class, 'id_servis', 'id_servis')->orderBy('waktu');
    }

    public function diagnosis(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'id_servis', 'id_servis')->orderBy('versi');
    }

    public function estimasi(): HasMany
    {
        return $this->hasMany(Estimasi::class, 'id_servis', 'id_servis')->orderBy('versi');
    }

    /** Catatan pengerjaan (fitur 31), urut waktu tulis. */
    public function catatan(): HasMany
    {
        return $this->hasMany(CatatanPengerjaan::class, 'id_servis', 'id_servis')->orderBy('waktu')->orderBy('id_catatan');
    }

    /** Hasil QC semua putaran (fitur 33). */
    public function qc(): HasMany
    {
        return $this->hasMany(QcHasil::class, 'id_servis', 'id_servis')->orderBy('putaran')->orderBy('id_qc');
    }

    /** Spare part yang dipakai (fitur 37). */
    public function penggunaan(): HasMany
    {
        return $this->hasMany(PenggunaanSparepart::class, 'id_servis', 'id_servis')->orderBy('waktu')->orderBy('id_penggunaan');
    }

    /** Putaran QC terakhir yang tercatat (null bila belum pernah). */
    public function qcPutaranTerakhir(): ?int
    {
        $putaran = $this->qc()->reorder('putaran', 'desc')->value('putaran');

        return $putaran === null ? null : (int) $putaran;
    }

    /** Semua butir pada putaran QC terakhir lulus? */
    public function qcLulusTerakhir(): bool
    {
        $putaran = $this->qcPutaranTerakhir();
        if ($putaran === null) {
            return false;
        }

        return ! $this->qc()->where('putaran', $putaran)->where('lulus', false)->exists();
    }

    /** Catatan pengerjaan hanya berlaku saat perangkat sudah disetujui/dikerjakan. */
    public function bolehCatatan(): bool
    {
        return in_array($this->status, [self::DISETUJUI, self::DIKERJAKAN, self::QUALITY_CONTROL], true);
    }

    /** Versi estimasi terakhir, apa pun statusnya (dipakai memilih draf/revisi). */
    public function estimasiTerakhir(): ?Estimasi
    {
        return $this->estimasi()->reorder('versi', 'desc')->first();
    }

    /** Estimasi yang sedang dipakai: draf baru, atau versi disetujui terakhir. */
    public function estimasiDisetujui(): ?Estimasi
    {
        return $this->estimasi()->reorder('versi', 'desc')->where('status', Estimasi::DISETUJUI)->first();
    }

    /** Total tagihan (ARCHITECTURE §3.1): dihitung, tidak disimpan. */
    public function totalTagihan(): float
    {
        return match ($this->status) {
            self::DIBATALKAN => 0.0,
            self::DITOLAK => (float) $this->biaya_pemeriksaan_salinan,
            default => $this->estimasiDisetujui()?->total() ?? 0.0,
        };
    }

    /** Servis pernah dikerjakan? Dipakai membedakan estimasi awal dan revisi. */
    public function pernahDikerjakan(): bool
    {
        return in_array($this->status, [self::DIKERJAKAN, self::QUALITY_CONTROL], true)
            || $this->riwayat()->where('ke_status', self::DIKERJAKAN)->exists();
    }

    /** Boleh diisi diagnosis/estimasi: sebelum persetujuan atau saat revisi. */
    public function bolehDiagnosis(): bool
    {
        return in_array($this->status, [self::MENUNGGU_DIAGNOSIS, self::DIKERJAKAN], true);
    }

    public function labelStatus(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }

    public function namaPerangkat(): string
    {
        return trim($this->merek.' '.$this->model);
    }
}
