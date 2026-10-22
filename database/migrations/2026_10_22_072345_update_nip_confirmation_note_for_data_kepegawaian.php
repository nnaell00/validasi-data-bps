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
                'confirmation_note' => 'NIP tidak sesuai format yang didukung. Verifikasi nilai sumber sebelum menentukan perbaikan.',
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
                'confirmation_note' => 'Periksa kandidat NIP baru yang disarankan dan konfirmasikan sebelum menyimpan perbaikan.',
            ]);
    }
};
