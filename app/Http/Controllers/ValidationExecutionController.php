<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BatchRow;
use App\Models\RowAnomaly;
use App\Models\ValidationBatch;
use App\Services\ValidationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ValidationExecutionController extends Controller
{
    /**
     * Menjalankan mesin validasi data pada batch tertentu.
     */
    public function execute(ValidationBatch $batch, ValidationEngine $engine): RedirectResponse
    {
        Gate::authorize('update', $batch);

        $batch->load(['profile.activeRules', 'rows']);
        $profile = $batch->profile;

        if (! $profile || ! $profile->is_active) {
            return back()->with('error', 'Profil validasi tidak aktif atau tidak ditemukan.');
        }

        // Hapus anomali sebelumnya jika ada untuk perhitungan ulang
        RowAnomaly::where('validation_batch_id', $batch->id)->delete();

        // Kumpulkan data baris (menggunakan current_data)
        $rowsData = [];
        $rowsMap = [];
        foreach ($batch->rows as $row) {
            $rowsData[$row->row_index] = $row->current_data;
            $rowsMap[$row->row_index] = $row;
        }

        // Eksekusi pemindaian mesin validasi
        $result = $engine->validate($profile, $rowsData);
        $this->persistNormalizedNips($result['normalized_nips'], $rowsMap);

        // Reset status anomali pada semua baris
        BatchRow::where('validation_batch_id', $batch->id)->update(['has_anomalies' => false]);

        // Simpan setiap anomali yang ditemukan
        foreach ($result['anomalies'] as $anomalyItem) {
            $rowIndex = $anomalyItem['row_index'];
            $batchRow = $rowsMap[$rowIndex] ?? null;

            if ($batchRow) {
                RowAnomaly::create([
                    'validation_batch_id' => $batch->id,
                    'batch_row_id' => $batchRow->id,
                    'column_name' => $anomalyItem['column_name'],
                    'column_index' => $anomalyItem['column_index'],
                    'invalid_value' => is_scalar($anomalyItem['original_value']) ? (string) $anomalyItem['original_value'] : json_encode($anomalyItem['original_value']),
                    'error_message' => $anomalyItem['error_message'],
                    'needs_confirmation' => $anomalyItem['needs_confirmation'] ?? false,
                    'confirmation_note' => $anomalyItem['confirmation_note'] ?? null,
                    'is_resolved' => false,
                ]);

                $batchRow->update(['has_anomalies' => true]);
            }
        }

        $newStatus = ($result['anomalies_count'] === 0) ? 'completed' : 'validated';

        $batch->update([
            'status' => $newStatus,
            'summary' => [
                'total_rows' => $result['total_rows'],
                'valid_rows' => $result['valid_rows_count'],
                'anomalous_rows' => $result['anomalous_rows_count'],
                'total_anomalies' => $result['anomalies_count'],
                'total_corrected' => $batch->rows()->where('is_corrected', true)->count(),
            ],
        ]);

        // Catat ke audit log
        AuditLog::create([
            'validation_batch_id' => $batch->id,
            'user_id' => Auth::id(),
            'action' => 'validation',
            'notes' => "Validasi selesai: {$result['valid_rows_count']} baris valid, {$result['anomalous_rows_count']} baris memuat anomali ({$result['anomalies_count']} kesalahan total).",
        ]);

        return redirect()->route('validation.results', $batch)
            ->with('success', "Proses validasi selesai! {$result['valid_rows_count']} baris valid dan {$result['anomalies_count']} anomali terdeteksi.");
    }

    /**
     * Menampilkan antarmuka hasil validasi data dan daftar anomali.
     */
    public function results(Request $request, ValidationBatch $batch): View
    {
        Gate::authorize('view', $batch);

        $batch->load(['profile', 'user']);

        $filter = $request->input('filter', 'all'); // all, anomalies_only, valid_only
        $search = $request->input('search');

        $rowsQuery = $batch->rows()->with('anomalies');

        if ($filter === 'anomalies_only') {
            $rowsQuery->where('has_anomalies', true);
        } elseif ($filter === 'valid_only') {
            $rowsQuery->where('has_anomalies', false);
        }

        if ($search) {
            $rowsQuery->where(function ($q) use ($search) {
                $q->where('current_data', 'like', "%{$search}%");
            });
        }

        $rows = $rowsQuery->paginate(15)->withQueryString();

        $stats = [
            'total_rows' => $batch->total_rows,
            'valid_rows' => $batch->rows()->where('has_anomalies', false)->count(),
            'anomalous_rows' => $batch->rows()->where('has_anomalies', true)->count(),
            'total_anomalies' => $batch->anomalies()->where('is_resolved', false)->count(),
            'resolved_anomalies' => $batch->anomalies()->where('is_resolved', true)->count(),
            'total_corrected' => $batch->rows()->where('is_corrected', true)->count(),
        ];

        return view('validation.results', compact('batch', 'rows', 'filter', 'search', 'stats'));
    }

    /**
     * Menampilkan form koreksi/perbaikan satu baris data.
     */
    public function editRow(ValidationBatch $batch, BatchRow $row): View
    {
        Gate::authorize('view', $batch);

        if ($row->validation_batch_id !== $batch->id) {
            abort(404);
        }

        $batch->load('profile.rules');
        $row->load('anomalies');

        // Petakan anomali per column_index untuk mempermudah visualisasi form
        $anomaliesByColIndex = [];
        foreach ($row->anomalies as $anomaly) {
            $anomaliesByColIndex[$anomaly->column_index][] = $anomaly;
        }

        return view('validation.edit-row', compact('batch', 'row', 'anomaliesByColIndex'));
    }

    /**
     * Menyimpan hasil koreksi data dari Petugas BPS dan mencatat ke audit log.
     */
    public function updateRow(Request $request, ValidationBatch $batch, BatchRow $row): RedirectResponse
    {
        Gate::authorize('update', $batch);

        if ($row->validation_batch_id !== $batch->id) {
            abort(404);
        }

        $headers = $batch->headers ?? [];
        $allowedColumns = implode(',', array_keys($headers));
        $validated = $request->validate([
            'data' => ['required', 'array:'.$allowedColumns],
            'data.*' => ['nullable', 'string'],
        ]);
        $submittedData = $validated['data'];
        $originalData = $row->original_data;

        foreach (ValidationBatch::PRESERVED_SOURCE_COLUMN_INDICES as $columnIndex) {
            if (! array_key_exists($columnIndex, $submittedData)) {
                continue;
            }

            $submittedValue = is_string($submittedData[$columnIndex])
                ? trim($submittedData[$columnIndex])
                : $submittedData[$columnIndex];

            if ((string) ($originalData[$columnIndex] ?? '') !== (string) ($submittedValue ?? '')) {
                return back()->withErrors([
                    "data.{$columnIndex}" => 'Kolom sumber tidak boleh diubah.',
                ])->withInput();
            }
        }

        $hasChanges = DB::transaction(function () use ($batch, $headers, $row, $submittedData): bool {
            $currentData = $row->current_data;
            $hasChanges = false;

            foreach ($submittedData as $columnIndex => $newValue) {
                $columnIndex = (int) $columnIndex;

                if (in_array($columnIndex, ValidationBatch::PRESERVED_SOURCE_COLUMN_INDICES, true)) {
                    continue;
                }

                $oldValue = $currentData[$columnIndex] ?? null;
                $trimmedNew = is_string($newValue) ? trim($newValue) : $newValue;
                $columnName = $headers[$columnIndex] ?? "Kolom #{$columnIndex}";

                if ((string) $oldValue === (string) $trimmedNew) {
                    continue;
                }

                $hasChanges = true;
                $currentData[$columnIndex] = $trimmedNew;

                AuditLog::create([
                    'validation_batch_id' => $batch->id,
                    'batch_row_id' => $row->id,
                    'user_id' => Auth::id(),
                    'column_name' => $columnName,
                    'old_value' => $oldValue === null ? null : (string) $oldValue,
                    'new_value' => $trimmedNew === null ? null : (string) $trimmedNew,
                    'action' => 'correction',
                    'notes' => "Koreksi baris #{$row->row_number} oleh ".Auth::user()->name,
                ]);

                RowAnomaly::where('batch_row_id', $row->id)
                    ->where('column_index', $columnIndex)
                    ->update([
                        'is_resolved' => true,
                        'corrected_value' => $trimmedNew === null ? null : (string) $trimmedNew,
                    ]);
            }

            if ($hasChanges) {
                $row->update([
                    'current_data' => $currentData,
                    'is_corrected' => true,
                ]);
            }

            return $hasChanges;
        });

        if ($hasChanges) {

            return redirect()->route('validation.results', $batch)
                ->with('success', "Baris ke-{$row->row_number} berhasil dikoreksi dan dicatat ke riwayat audit.");
        }

        return redirect()->route('validation.results', $batch)
            ->with('info', "Tidak ada perubahan nilai pada baris ke-{$row->row_number}.");
    }

    /**
     * Menjalankan validasi ulang (re-validate) pada seluruh data yang telah dikoreksi.
     */
    public function revalidate(ValidationBatch $batch, ValidationEngine $engine): RedirectResponse
    {
        Gate::authorize('update', $batch);

        $batch->load(['profile.activeRules', 'rows']);
        $profile = $batch->profile;

        // Bersihkan anomali lama
        RowAnomaly::where('validation_batch_id', $batch->id)->delete();

        $rowsData = [];
        $rowsMap = [];
        foreach ($batch->rows as $row) {
            $rowsData[$row->row_index] = $row->current_data;
            $rowsMap[$row->row_index] = $row;
        }

        $result = $engine->validate($profile, $rowsData);
        $this->persistNormalizedNips($result['normalized_nips'], $rowsMap);

        // Reset status has_anomalies
        BatchRow::where('validation_batch_id', $batch->id)->update(['has_anomalies' => false]);

        // Simpan anomali yang masih tersisa
        foreach ($result['anomalies'] as $anomalyItem) {
            $rowIndex = $anomalyItem['row_index'];
            $batchRow = $rowsMap[$rowIndex] ?? null;

            if ($batchRow) {
                RowAnomaly::create([
                    'validation_batch_id' => $batch->id,
                    'batch_row_id' => $batchRow->id,
                    'column_name' => $anomalyItem['column_name'],
                    'column_index' => $anomalyItem['column_index'],
                    'invalid_value' => is_scalar($anomalyItem['original_value']) ? (string) $anomalyItem['original_value'] : json_encode($anomalyItem['original_value']),
                    'error_message' => $anomalyItem['error_message'],
                    'needs_confirmation' => $anomalyItem['needs_confirmation'] ?? false,
                    'confirmation_note' => $anomalyItem['confirmation_note'] ?? null,
                    'is_resolved' => false,
                ]);

                $batchRow->update(['has_anomalies' => true]);
            }
        }

        $newStatus = ($result['anomalies_count'] === 0) ? 'completed' : 'validated';

        $batch->update([
            'status' => $newStatus,
            'summary' => [
                'total_rows' => $result['total_rows'],
                'valid_rows' => $result['valid_rows_count'],
                'anomalous_rows' => $result['anomalous_rows_count'],
                'total_anomalies' => $result['anomalies_count'],
                'total_corrected' => $batch->rows()->where('is_corrected', true)->count(),
            ],
        ]);

        AuditLog::create([
            'validation_batch_id' => $batch->id,
            'user_id' => Auth::id(),
            'action' => 'revalidation',
            'notes' => "Validasi ulang selesai: {$result['valid_rows_count']} baris bersih, {$result['anomalies_count']} anomali tersisa.",
        ]);

        if ($result['anomalies_count'] === 0) {
            return redirect()->route('validation.results', $batch)
                ->with('success', 'Hebat! Validasi ulang berhasil. Seluruh data kini bersih dari anomali (100% Valid) dan siap diekspor.');
        }

        return redirect()->route('validation.results', $batch)
            ->with('info', "Validasi ulang selesai: Masih tersisa {$result['anomalies_count']} anomali pada {$result['anomalous_rows_count']} baris yang memerlukan perbaikan.");
    }

    /**
     * Persist normalized NIPs without changing the original imported data.
     *
     * @param  array<int, array<int|string, string>>  $normalizedNips
     * @param  array<int, BatchRow>  $rowsMap
     */
    private function persistNormalizedNips(array $normalizedNips, array $rowsMap): void
    {
        foreach ($normalizedNips as $rowIndex => $nipValues) {
            $batchRow = $rowsMap[$rowIndex] ?? null;

            if ($batchRow === null) {
                continue;
            }

            $currentData = $batchRow->current_data;
            foreach ($nipValues as $columnKey => $normalizedNip) {
                $currentData[$columnKey] = $normalizedNip;
            }

            if ($batchRow->current_data !== $currentData) {
                $batchRow->update(['current_data' => $currentData]);
            }
        }
    }
}
