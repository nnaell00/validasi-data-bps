<?php

namespace App\Http\Controllers;

use App\Models\BatchRow;
use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama sesuai peran pengguna.
     */
    public function index(): View
    {
        $user = Auth::user();

        if ($user->isPetugas()) {
            $processedStatuses = ['validated', 'completed'];
            $processedRows = BatchRow::query()
                ->whereHas('batch', function (Builder $query) use ($user, $processedStatuses): void {
                    $query->where('user_id', $user->id)
                        ->whereIn('status', $processedStatuses);
                });

            $stats = [
                'processed_batches' => ValidationBatch::query()
                    ->where('user_id', $user->id)
                    ->whereIn('status', $processedStatuses)
                    ->count(),
                'valid_rows' => (clone $processedRows)
                    ->where('has_anomalies', false)
                    ->count(),
                'anomalous_rows' => (clone $processedRows)
                    ->where('has_anomalies', true)
                    ->count(),
                'follow_up_rows' => (clone $processedRows)
                    ->where('has_anomalies', true)
                    ->whereHas('unresolvedAnomalies')
                    ->count(),
            ];
        } else {
            $stats = [
                'total_users' => User::count(),
                'total_batches' => 0,
                'total_records_checked' => 0,
                'total_anomalies' => 0,
                'total_corrected' => 0,
            ];
        }

        $activeProfile = ValidationProfile::query()
            ->where('slug', 'data-kepegawaian')
            ->where('is_active', true)
            ->first();

        return view('dashboard.index', compact('user', 'stats', 'activeProfile'));
    }
}
