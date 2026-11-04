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

        if ($user->isAdmin()) {
            $stats = [
                'total_users' => User::query()->count(),
                'admin_users' => User::query()->where('role', 'admin')->count(),
                'petugas_users' => User::query()->where('role', 'petugas')->count(),
                'total_batches' => ValidationBatch::query()->count(),
                'total_profiles' => ValidationProfile::query()->count(),
            ];

            $recentBatches = ValidationBatch::query()
                ->whereIn('status', ['validated', 'completed'])
                ->with(['user:id,name'])
                ->withCount('rows')
                ->withCount([
                    'rows as anomalous_rows_count' => fn (Builder $query): Builder => $query
                        ->whereHas('anomalies'),
                ])
                ->latest('updated_at')
                ->latest('id')
                ->limit(5)
                ->get();

            return view('dashboard.index', compact(
                'user',
                'stats',
                'recentBatches',
            ));
        }

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
                ->whereDoesntHave('anomalies')
                ->count(),
            'anomalous_rows' => (clone $processedRows)
                ->whereHas('anomalies')
                ->count(),
            'follow_up_rows' => (clone $processedRows)
                ->whereHas('anomalies')
                ->whereHas('unresolvedAnomalies')
                ->count(),
        ];

        $activeProfile = ValidationProfile::query()
            ->where('slug', 'data-kepegawaian')
            ->where('is_active', true)
            ->first();

        return view('dashboard.index', compact('user', 'stats', 'activeProfile'));
    }
}
