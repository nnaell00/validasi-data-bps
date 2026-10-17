<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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

        // Statistik ringkasan (akan terhubung dengan modul validasi pada tahap berikutnya)
        $stats = [
            'total_users' => User::count(),
            'total_batches' => 0,
            'total_records_checked' => 0,
            'total_anomalies' => 0,
            'total_corrected' => 0,
        ];

        return view('dashboard.index', compact('user', 'stats'));
    }
}
