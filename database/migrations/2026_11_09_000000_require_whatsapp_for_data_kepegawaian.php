<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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
            ->where('column_name', 'No. WA')
            ->update([
                'is_required' => true,
                'error_message' => 'Nomor WhatsApp wajib diisi dan harus berformat nomor Indonesia yang valid (contoh: 08123456789 atau 628123456789).',
                'confirmation_note' => null,
            ]);
    }

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
            ->where('column_name', 'No. WA')
            ->update([
                'is_required' => false,
                'error_message' => 'Nomor WhatsApp jika diisi harus berformat nomor Indonesia yang valid (contoh: 08123456789 atau 628123456789).',
                'confirmation_note' => 'Aturan khusus terpenuhi: Bersifat opsional, nilai kosong diizinkan.',
            ]);
    }
};
