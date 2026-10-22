<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $profileId = DB::table('validation_profiles')
            ->where('slug', 'data-kepegawaian')
            ->value('id');

        if ($profileId === null) {
            return;
        }

        DB::table('validation_rules')
            ->where('validation_profile_id', $profileId)
            ->where('column_name', 'NIP')
            ->update([
                'parameters' => json_encode(['pattern' => '/^[0-9]{18}$/D'], JSON_THROW_ON_ERROR),
                'error_message' => 'NIP harus terdiri tepat dari 18 digit angka.',
                'confirmation_note' => 'Periksa kandidat NIP baru yang disarankan dan konfirmasikan sebelum menyimpan perbaikan.',
            ]);

        DB::table('validation_rules')
            ->where('validation_profile_id', $profileId)
            ->where('column_name', 'Jenis Kelamin')
            ->update([
                'parameters' => json_encode([
                    'options' => ['L', 'LK', 'Laki-laki', 'P', 'PR', 'Perempuan', 'Wanita'],
                ], JSON_THROW_ON_ERROR),
                'error_message' => 'Jenis kelamin harus L, LK, Laki-laki, P, PR, Perempuan, atau Wanita.',
                'needs_confirmation' => false,
                'confirmation_note' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $profileId = DB::table('validation_profiles')
            ->where('slug', 'data-kepegawaian')
            ->value('id');

        if ($profileId === null) {
            return;
        }

        DB::table('validation_rules')
            ->where('validation_profile_id', $profileId)
            ->where('column_name', 'NIP')
            ->update([
                'parameters' => json_encode([
                    'pattern' => '/^(\d{18}|\d{8}\s\d{6}\s\d{1}\s\d{3})$/',
                ], JSON_THROW_ON_ERROR),
                'error_message' => 'NIP harus terdiri dari 18 digit angka (dengan atau tanpa spasi format BKN).',
                'confirmation_note' => 'Perlu konfirmasi pihak BPS apakah format NIP wajib 18 digit murni tanpa spasi atau boleh memakai pemisah spasi format BKN.',
            ]);

        DB::table('validation_rules')
            ->where('validation_profile_id', $profileId)
            ->where('column_name', 'Jenis Kelamin')
            ->update([
                'parameters' => json_encode([
                    'options' => ['L', 'P', 'Laki-laki', 'Perempuan', 'Pria', 'Wanita'],
                ], JSON_THROW_ON_ERROR),
                'error_message' => 'Jenis kelamin harus salah satu dari: L, P, Laki-laki, atau Perempuan.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi kode jenis kelamin yang dipakai BPS (apakah 1 huruf L/P atau kata lengkap).',
            ]);
    }
};
