<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CatatanRequest;
use App\Http\Requests\Admin\DiagnosisRequest;
use App\Http\Requests\Admin\EstimasiRequest;
use App\Http\Requests\Admin\FotoServisRequest;
use App\Http\Requests\Admin\KelolaServisRequest;
use App\Http\Requests\Admin\PenggunaanSparepartRequest;
use App\Http\Requests\Admin\QcRequest;
use App\Http\Requests\Admin\ServisRequest;
use App\Http\Requests\Admin\UbahStatusRequest;
use App\Models\Estimasi;
use App\Models\Pelanggan;
use App\Models\PengaturanBisnis;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Models\Sparepart;
use App\Services\DiagnosisService;
use App\Services\EstimasiService;
use App\Services\PengerjaanService;
use App\Services\ServisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service order area Admin/Owner: buat, daftar, cari, detail, ubah info,
 * foto, assign teknisi, tanda terima, batal (fitur 15–22).
 */
class ServisController extends Controller
{
    public function __construct(private readonly ServisService $servis) {}

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $status = (string) $request->query('status', '');

        $daftar = Servis::query()
            ->with(['pelanggan', 'teknisi'])
            ->when($cari !== '', function ($query) use ($cari): void {
                $query->where(function ($query) use ($cari): void {
                    $query->where('nomor_servis', 'like', "%{$cari}%")
                        ->orWhere('kode_tracking', 'like', "%{$cari}%")
                        ->orWhere('merek', 'like', "%{$cari}%")
                        ->orWhere('model', 'like', "%{$cari}%")
                        ->orWhereHas('pelanggan', fn ($p) => $p->where('nama', 'like', "%{$cari}%")
                            ->orWhere('no_hp', 'like', "%{$cari}%"));
                });
            })
            ->when(array_key_exists($status, Servis::LABEL_STATUS), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id_servis')
            ->paginate(20)
            ->withQueryString();

        return view('admin.servis.index', compact('daftar', 'cari', 'status'));
    }

    /** Servis yang lewat SLA (fitur 54). */
    public function terlambat(): View
    {
        return view('admin.servis.index', [
            'daftar' => Servis::query()->terlambat()->with(['pelanggan', 'teknisi'])
                ->orderBy('tanggal_masuk')->paginate(20),
            'cari' => '',
            'status' => '',
            'terlambat' => true,
        ]);
    }

    public function create(): View
    {
        return view('admin.servis.create', [
            'daftarPelanggan' => Pelanggan::where('status', Pelanggan::STATUS_AKTIF)->orderBy('nama')->get(),
        ]);
    }

    public function store(ServisRequest $request): RedirectResponse
    {
        $servis = $this->servis->buatServis($request->validated(), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', "Servis {$servis->nomor_servis} dibuat.");
    }

    public function show(Servis $servis): View
    {
        $this->authorize('lihat', $servis);

        $servis->load([
            'pelanggan', 'teknisi', 'riwayat.diubahOleh', 'foto.diunggahOleh',
            'diagnosis.pembuat', 'estimasi.item', 'estimasi.pembuat',
            'catatan.penulis', 'qc.pemeriksa', 'penggunaan.sparepart',
        ]);

        return view('admin.servis.show', [
            'servis' => $servis,
            'daftarTeknisi' => Pengguna::where('peran', Pengguna::PERAN_TEKNISI)
                ->where('status_akun', Pengguna::STATUS_AKTIF)
                ->orderBy('nama')
                ->get(),
            'pilihanSumber' => EstimasiService::pilihanSumber(),
            'daftarSparepart' => Sparepart::query()
                ->where('status', Sparepart::STATUS_AKTIF)
                ->withSum('mutasi as stok', 'jumlah')
                ->orderBy('nama')
                ->get(),
        ]);
    }

    public function update(ServisRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $this->servis->ubahInfo($servis, $request->validated(), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Informasi servis diperbarui.');
    }

    public function foto(FotoServisRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('lihat', $servis);

        $this->servis->tambahFoto($servis, $request->file('foto'), $request->validated('jenis'), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Foto diunggah.');
    }

    public function teknisi(KelolaServisRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('kelola', $servis);

        $this->servis->assignTeknisi($servis, Pengguna::findOrFail($request->validated('id_teknisi')), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Teknisi ditugaskan.');
    }

    public function batal(KelolaServisRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('kelola', $servis);

        $this->servis->batalkan($servis, $request->user(), $request->validated('alasan'));

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Servis dibatalkan.');
    }

    /** Masuk tahap diagnosis: diterima -> menunggu_diagnosis. */
    public function mulaiDiagnosis(Request $request, Servis $servis): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $this->servis->pindahStatus($servis, Servis::MENUNGGU_DIAGNOSIS, $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Servis masuk tahap diagnosis.');
    }

    /** Diagnosis (fitur 23): versi baru setiap koreksi. */
    public function diagnosis(DiagnosisRequest $request, Servis $servis, DiagnosisService $diagnosis): RedirectResponse
    {
        $this->authorize('diagnosis', $servis);

        $baru = $diagnosis->catat($servis, $request->validated(), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', "Diagnosis versi {$baru->versi} dicatat.");
    }

    /** Simpan/perbarui draf estimasi (fitur 24). */
    public function simpanEstimasi(EstimasiRequest $request, Servis $servis, EstimasiService $estimasi): RedirectResponse
    {
        $this->authorize('diagnosis', $servis);

        $draf = $estimasi->simpanDraf($servis, $request->validated('items'), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', "Draf estimasi versi {$draf->versi} disimpan.");
    }

    /** Kirim estimasi ke pelanggan (fitur 25). */
    public function kirimEstimasi(Request $request, Servis $servis, Estimasi $estimasi, EstimasiService $service): RedirectResponse
    {
        $this->authorize('kirimEstimasi', $servis);
        abort_unless($estimasi->id_servis === $servis->id_servis, 404);

        $service->kirim($estimasi, $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Estimasi dikirim ke pelanggan.');
    }

    /** Versi baru dari estimasi disetujui (fitur 28). */
    public function revisiEstimasi(Request $request, Servis $servis, EstimasiService $service): RedirectResponse
    {
        $this->authorize('diagnosis', $servis);

        $draf = $service->revisi($servis, $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', "Draf revisi estimasi versi {$draf->versi} dibuat.");
    }

    /** Estimasi ditolak: perangkat siap diambil, tagihan = biaya pemeriksaan (fitur 26). */
    public function kembalikan(Request $request, Servis $servis): RedirectResponse
    {
        $this->authorize('kelola', $servis);

        $this->servis->pindahStatus($servis, Servis::SIAP_DIAMBIL, $request->user(), 'Estimasi ditolak pelanggan; perangkat dikembalikan.');

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Servis ditandai siap diambil.');
    }

    /** Ubah status pengerjaan (fitur 30): mulai kerjakan / kirim ke QC. */
    public function status(UbahStatusRequest $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $pengerjaan->ubahStatus($servis, $request->validated('status'), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Status servis: '.$servis->refresh()->labelStatus().'.');
    }

    /** Catatan pengerjaan (fitur 31). */
    public function catatan(CatatanRequest $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $pengerjaan->catatCatatan($servis, $request->validated('isi'), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Catatan pengerjaan ditambahkan.');
    }

    /** Pemakaian spare part (fitur 37). */
    public function pakaiSparepart(PenggunaanSparepartRequest $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $pengerjaan->catatPenggunaan(
            $servis,
            Sparepart::findOrFail($request->validated('id_sparepart')),
            $request->validated('jumlah'),
            $request->user(),
        );

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Penggunaan spare part dicatat.');
    }

    /** Checklist QC satu putaran (fitur 33). */
    public function qc(QcRequest $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $hasil = $pengerjaan->catatQc($servis, $request->validated('butir'), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', $hasil['lulus']
                ? "QC putaran {$hasil['putaran']} lulus semua. Tandai siap diambil."
                : "QC putaran {$hasil['putaran']} tidak lulus; servis kembali dikerjakan.");
    }

    /** Tandai siap diambil setelah QC lulus (fitur 34). */
    public function siapDiambil(Request $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $pengerjaan->siapDiambil($servis, $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Servis siap diambil.');
    }

    /** Tanda terima + QR tracking (fitur 21), dicetak sebagai PDF. */
    public function tandaTerima(Servis $servis): Response
    {
        $this->authorize('lihat', $servis);

        $servis->load(['pelanggan', 'dibuatOleh']);

        $qr = QrCode::format('svg')->size(140)->margin(1)
            ->generate(route('publik.lacak.hasil', $servis->kode_tracking));

        return Pdf::loadView('cetak.tanda-terima', [
            'servis' => $servis,
            'qr' => $qr,
            'bisnis' => [
                'nama' => PengaturanBisnis::nilai('nama_bisnis', config('app.name')),
                'alamat' => PengaturanBisnis::nilai('alamat_bisnis', ''),
            ],
        ])->stream("tanda-terima-{$servis->nomor_servis}.pdf");
    }
}
