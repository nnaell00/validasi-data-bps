<?php

namespace App\Http\Controllers;

use App\Models\ValidationProfile;
use App\Models\ValidationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ValidationProfileController extends Controller
{
    /**
     * Menampilkan daftar profil validasi data.
     */
    public function index(): View
    {
        $profiles = ValidationProfile::withCount('rules')
            ->with('creator')
            ->latest()
            ->paginate(10);

        return view('profiles.index', compact('profiles'));
    }

    /**
     * Menampilkan rincian profil validasi beserta aturan-aturan tiap kolom.
     */
    public function show(ValidationProfile $profile): View
    {
        $profile->load(['rules', 'creator']);

        return view('profiles.show', compact('profile'));
    }

    /**
     * Mengubah status aktif / non-aktif profil validasi.
     */
    public function toggleStatus(ValidationProfile $profile): RedirectResponse
    {
        $profile->update([
            'is_active' => ! $profile->is_active,
        ]);

        $statusText = $profile->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Profil {$profile->name} berhasil {$statusText}.");
    }

    /**
     * Mengubah status aktif / non-aktif satu aturan validasi tertentu.
     */
    public function toggleRuleStatus(ValidationProfile $profile, ValidationRule $rule): RedirectResponse
    {
        if ($rule->validation_profile_id !== $profile->id) {
            abort(404);
        }

        $rule->update([
            'is_active' => ! $rule->is_active,
        ]);

        $statusText = $rule->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Aturan untuk kolom '{$rule->column_name}' berhasil {$statusText}.");
    }

    /**
     * Menampilkan form edit aturan validasi.
     */
    public function editRule(ValidationProfile $profile, ValidationRule $rule): View
    {
        if ($rule->validation_profile_id !== $profile->id) {
            abort(404);
        }

        return view('profiles.edit-rule', compact('profile', 'rule'));
    }

    /**
     * Memperbarui aturan validasi (pesan kesalahan, status wajib, dan catatan konfirmasi).
     */
    public function updateRule(Request $request, ValidationProfile $profile, ValidationRule $rule): RedirectResponse
    {
        if ($rule->validation_profile_id !== $profile->id) {
            abort(404);
        }

        $validated = $request->validate([
            'error_message' => ['required', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'needs_confirmation' => ['boolean'],
            'confirmation_note' => ['nullable', 'string', 'max:500'],
            'pattern' => ['nullable', 'string', 'max:255'],
        ], [
            'error_message.required' => 'Pesan kesalahan anomali wajib diisi.',
        ]);

        $params = $rule->parameters ?? [];
        if ($request->filled('pattern')) {
            $params['pattern'] = $request->input('pattern');
        }

        $rule->update([
            'error_message' => $validated['error_message'],
            'is_required' => $request->has('is_required'),
            'needs_confirmation' => $request->has('needs_confirmation'),
            'confirmation_note' => $validated['confirmation_note'] ?? null,
            'parameters' => $params,
        ]);

        return redirect()->route('profiles.show', $profile)
            ->with('success', "Aturan validasi untuk kolom '{$rule->column_name}' berhasil diperbarui.");
    }
}
