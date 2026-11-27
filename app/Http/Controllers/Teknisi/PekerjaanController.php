<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CatatanRequest;
use App\Http\Requests\Admin\DiagnosisRequest;
use App\Http\Requests\Admin\EstimasiRequest;
use App\Http\Requests\Admin\FotoServisRequest;
use App\Http\Requests\Admin\PenggunaanSparepartRequest;
use App\Http\Requests\Admin\QcRequest;
use App\Http\Requests\Admin\ServisRequest;
use App\Http\Requests\Admin\UbahStatusRequest;
use App\Models\Servis;
use App\Models\Sparepart;
use App\Services\DiagnosisService;
use App\Services\EstimasiService;
use App\Services\PengerjaanService;
use App\Services\ServisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Area teknisi: daftar pekerjaan yang ditugaskan dan detailnya.
 */
class PekerjaanController extends Controller
{
    public function __construct(private readonly ServisService $servis) {}

    public function index(Request $request): View
    {
        $daftar = Servis::query()
            ->where('id_teknisi', $request->user()->id_pengguna)
            ->with(['pelanggan', 'teknisi'])
            ->orderByRaw("case when status in ('selesai','dibatalkan') then 1 else 0 end")
            ->orderByDesc('id_servis')
            ->paginate(20);

        return view('teknisi.pekerjaan.index', compact('daftar'));
    }

    public function show(Servis $servis): View
    {
        $this->authorize('lihat', $servis);

        $servis->load([
            'pelanggan', 'teknisi', 'riwayat.diubahOleh', 'foto.diunggahOleh',
            'diagnosis.pembuat', 'estimasi.item', 'estimasi.pembuat',
            'catatan.penulis', 'qc.pemeriksa', 'penggunaan.sparepart',
        ]);

        return view('teknisi.pekerjaan.show', [
            'servis' => $servis,
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
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', 'Informasi servis diperbarui.');
    }

    public function foto(FotoServisRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('lihat', $servis);

        $this->servis->tambahFoto($servis, $request->file('foto'), $request->validated('jenis'), $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', 'Foto diunggah.');
    }

    /** Masuk tahap diagnosis (teknisi yang ditugaskan). */
    public function mulaiDiagnosis(Request $request, Servis $servis): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $this->servis->pindahStatus($servis, Servis::MENUNGGU_DIAGNOSIS, $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', 'Servis masuk tahap diagnosis.');
    }

    /** Diagnosis (fitur 23). */
    public function diagnosis(DiagnosisRequest $request, Servis $servis, DiagnosisService $diagnosis): RedirectResponse
    {
        $this->authorize('diagnosis', $servis);

        $baru = $diagnosis->catat($servis, $request->validated(), $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', "Diagnosis versi {$baru->versi} dicatat.");
    }

    /** Draf estimasi (fitur 24). */
    public function simpanEstimasi(EstimasiRequest $request, Servis $servis, EstimasiService $estimasi): RedirectResponse
    {
        $this->authorize('diagnosis', $servis);

        $draf = $estimasi->simpanDraf($servis, $request->validated('items'), $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', "Draf estimasi versi {$draf->versi} disimpan.");
    }

    /** Revisi estimasi (fitur 28). */
    public function revisiEstimasi(Request $request, Servis $servis, EstimasiService $service): RedirectResponse
    {
        $this->authorize('diagnosis', $servis);

        $draf = $service->revisi($servis, $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', "Draf revisi estimasi versi {$draf->versi} dibuat.");
    }

    /** Ubah status pengerjaan (fitur 30). */
    public function status(UbahStatusRequest $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $pengerjaan->ubahStatus($servis, $request->validated('status'), $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', 'Status servis: '.$servis->refresh()->labelStatus().'.');
    }

    /** Catatan pengerjaan (fitur 31). */
    public function catatan(CatatanRequest $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $pengerjaan->catatCatatan($servis, $request->validated('isi'), $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
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
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', 'Penggunaan spare part dicatat.');
    }

    /** QC (fitur 33). */
    public function qc(QcRequest $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $hasil = $pengerjaan->catatQc($servis, $request->validated('butir'), $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', $hasil['lulus']
                ? "QC putaran {$hasil['putaran']} lulus semua. Tandai siap diambil."
                : "QC putaran {$hasil['putaran']} tidak lulus; servis kembali dikerjakan.");
    }

    /** Siap diambil (fitur 34). */
    public function siapDiambil(Request $request, Servis $servis, PengerjaanService $pengerjaan): RedirectResponse
    {
        $this->authorize('ubah', $servis);

        $pengerjaan->siapDiambil($servis, $request->user());

        return redirect()
            ->route('teknisi.pekerjaan.show', $servis)
            ->with('status', 'Servis siap diambil.');
    }
}
