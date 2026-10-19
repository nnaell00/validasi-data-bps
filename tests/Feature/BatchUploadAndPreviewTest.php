<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use App\Services\DummyDataGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BatchUploadAndPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_download_dummy_excel_template(): void
    {
        $user = User::create([
            'name' => 'Petugas BPS Test',
            'email' => 'petugas.test@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/batches/dummy-template');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=data_kepegawaian_dummy_bps_purwakarta.xlsx');
    }

    public function test_empty_validation_history_renders_centered_empty_state_without_table_headers(): void
    {
        $user = User::create([
            'name' => 'Petugas BPS Test',
            'email' => 'petugas-kosong.test@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('batches.index'));

        $response->assertOk();
        $response->assertSee('Belum ada berkas validasi yang diunggah');
        $response->assertSee('min-h-64 items-center justify-center', false);
        $response->assertSee(route('batches.dummy-template'), false);
        $response->assertSee(route('batches.create'), false);
        $response->assertDontSee('Nama Berkas Excel');
    }

    public function test_petugas_can_upload_excel_and_see_raw_preview(): void
    {
        $user = User::create([
            'name' => 'Petugas BPS Test',
            'email' => 'petugas.test@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $profile = ValidationProfile::create([
            'name' => 'Data Kepegawaian',
            'slug' => 'data-kepegawaian',
            'is_active' => true,
        ]);
        $this->travelTo(Carbon::parse('2026-10-19 06:31:00', 'UTC'));

        // Hasilkan file dummy Excel nyata menggunakan DummyDataGenerator
        $generator = new DummyDataGenerator;
        $dummyPath = $generator->generate();

        $uploadedFile = new UploadedFile(
            $dummyPath,
            'test_data_kepegawaian.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($user)->post('/batches/upload', [
            'validation_profile_id' => $profile->id,
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('validation_batches', [
            'file_name' => 'test_data_kepegawaian.xlsx',
            'status' => 'uploaded',
        ]);

        $this->assertDatabaseCount('batch_rows', 15);

        // Akses halaman preview
        $batch = ValidationBatch::first();
        $previewResponse = $this->actingAs($user)->get("/batches/{$batch->id}/preview");

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Pratinjau Data Mentah');
        $previewResponse->assertSee('Ahmad Fauzi');
        $previewResponse->assertSee('Jalankan Validasi Sekarang');
        $previewResponse->assertSee('19/10/2026 13:31 WIB');
        $this->assertSame('2026-10-19 06:31:00', $batch->refresh()->created_at->format('Y-m-d H:i:s'));

        $historyResponse = $this->actingAs($user)->get(route('batches.index'));

        $historyResponse->assertOk();
        $historyResponse->assertSee('19/10/2026 13:31 WIB');
        $historyResponse->assertSee('Nama Berkas Excel');
        $historyResponse->assertSee('Profil Data');
        $historyResponse->assertSee('Diupload Oleh');
        $historyResponse->assertSee('Total Baris');
        $historyResponse->assertSee('Waktu Unggah');
    }
}
