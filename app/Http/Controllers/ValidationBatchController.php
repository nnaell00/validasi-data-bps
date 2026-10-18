<?php

namespace App\Http\Controllers;

use App\Models\BatchRow;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use App\Services\DummyDataGenerator;
use App\Services\ExcelDataParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ValidationBatchController extends Controller
{
    /**
     * Menampilkan riwayat unggahan berkas validasi.
     */
    public function index(): View
    {
        $user = Auth::user();

        $batches = ValidationBatch::with(['profile', 'user'])
            ->when(! $user->isAdmin(), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        return view('batches.index', compact('batches'));
    }

    /**
     * Menampilkan form unggah file Excel data.
     */
    public function create(): View
    {
        $profiles = ValidationProfile::where('is_active', true)->get();

        return view('batches.create', compact('profiles'));
    }

    /**
     * Memproses file Excel yang diunggah dan menyimpannya ke database.
     */
    public function store(Request $request, ExcelDataParser $parser): RedirectResponse
    {
        $request->validate([
            'validation_profile_id' => ['required', 'exists:validation_profiles,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'], // Maksimal 10MB
        ], [
            'validation_profile_id.required' => 'Profil validasi data wajib dipilih.',
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'Format berkas harus berekstensi .xlsx atau .xls.',
            'file.max' => 'Ukuran file tidak boleh melebihi 10 MB.',
        ]);

        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();
        $storedPath = $uploadedFile->store('excel_uploads', 'local');

        $fullPath = Storage::disk('local')->path($storedPath);
        $parsed = $parser->parse($fullPath);

        if (empty($parsed['rows'])) {
            Storage::disk('local')->delete($storedPath);

            return back()->with('error', 'File Excel kosong atau tidak memiliki baris data.');
        }

        $batch = ValidationBatch::create([
            'user_id' => Auth::id(),
            'validation_profile_id' => $request->input('validation_profile_id'),
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'total_rows' => count($parsed['rows']),
            'status' => 'uploaded',
            'headers' => $parsed['headers'],
            'summary' => [
                'total_rows' => count($parsed['rows']),
                'valid_rows' => 0,
                'anomalous_rows' => 0,
                'total_anomalies' => 0,
                'total_corrected' => 0,
            ],
        ]);

        // Simpan baris data mentah ke tabel batch_rows (Data sumber aman dan terjaga)
        foreach ($parsed['rows'] as $index => $rowData) {
            BatchRow::create([
                'validation_batch_id' => $batch->id,
                'row_index' => $index,
                'row_number' => $index + 1,
                'original_data' => $rowData,
                'current_data' => $rowData,
                'has_anomalies' => false,
                'is_corrected' => false,
            ]);
        }

        return redirect()->route('batches.preview', $batch)
            ->with('success', "File '{$fileName}' berhasil diunggah ({$batch->total_rows} baris data ditemukan). Silakan periksa pratinjau data mentah di bawah.");
    }

    /**
     * Menampilkan pratinjau data mentah Excel sebelum divalidasi.
     */
    public function preview(ValidationBatch $batch): View
    {
        Gate::authorize('view', $batch);

        $batch->load(['profile', 'user']);
        $rows = $batch->rows()->paginate(15);

        return view('batches.preview', compact('batch', 'rows'));
    }

    /**
     * Mengunduh file template Excel sintetis (dummy) untuk demonstrasi & pengujian TA.
     */
    public function downloadDummy(DummyDataGenerator $generator): BinaryFileResponse
    {
        $filePath = $generator->generate();

        return response()->download(
            $filePath,
            'data_kepegawaian_dummy_bps_purwakarta.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
