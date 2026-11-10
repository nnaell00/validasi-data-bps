<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use App\Services\DummyDataGenerator;
use App\Services\ExcelDataParser;
use Database\Seeders\ValidationProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    public function test_parser_detects_header_after_metadata_and_preserves_blank_column_positions(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'bps-header-mapping-');
        if ($filePath === false) {
            self::fail('File sementara untuk pengujian header tidak dapat dibuat.');
        }

        try {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getActiveSheet()->fromArray([
                ['Daftar pegawai BPS Kabupaten Purwakarta'],
                ['Nama', 'NIP', null, 'Golongan', 'Pendidikan', 'Tanggal Lahir', 'Masa Kerja', 'Jenis Kelamin'],
                ['Pegawai Sintetis', '123456789012345678', null, 'III/a', 'S1', '1980-01-01', '10 Thn 2 Bln', 'L'],
            ], null, 'A1');
            (new Xlsx($spreadsheet))->save($filePath);

            $parsed = (new ExcelDataParser)->parse($filePath, 'data-kepegawaian');
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $this->assertSame(1, $parsed['header_row_index']);
        $this->assertSame([
            'Nama',
            'NIP',
            'Kolom_3',
            'Golongan',
            'Pendidikan',
            'Tanggal Lahir',
            'Masa Kerja',
            'Jenis Kelamin',
        ], $parsed['headers']);
        $this->assertSame('III/a', $parsed['rows'][0][3]);
        $this->assertSame('S1', $parsed['rows'][0][4]);
        $this->assertSame('L', $parsed['rows'][0][7]);
    }

    public function test_parser_recognizes_original_bps_header_variants(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'bps-original-header-');
        if ($filePath === false) {
            self::fail('File sementara untuk pengujian header BPS tidak dapat dibuat.');
        }

        try {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getActiveSheet()->fromArray([
                ['Nama', 'Pendidikan (SK)', 'Gol.Akhir', 'Tgl.Lahir', 'MK', 'Jenis Kelamin'],
                ['Pegawai Sintetis', 'S1', 'IV/a', '1980-01-01', '12 Th 4 Bln', 'P'],
            ], null, 'A1');
            (new Xlsx($spreadsheet))->save($filePath);

            $parsed = (new ExcelDataParser)->parse($filePath, 'data-kepegawaian');
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $this->assertSame(0, $parsed['header_row_index']);
        $this->assertSame('S1', $parsed['rows'][0][1]);
        $this->assertSame('IV/a', $parsed['rows'][0][2]);
        $this->assertSame('1980-01-01', $parsed['rows'][0][3]);
        $this->assertSame('12 Th 4 Bln', $parsed['rows'][0][4]);
    }

    public function test_uploaded_dummy_aliases_are_used_by_validation_and_employee_summary(): void
    {
        Storage::fake('local');
        $this->seed(ValidationProfileSeeder::class);
        $profile = ValidationProfile::query()->where('slug', 'data-kepegawaian')->firstOrFail();
        $user = User::create([
            'name' => 'Petugas Alias Uji',
            'email' => 'petugas-alias@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $filePath = tempnam(sys_get_temp_dir(), 'bps-alias-upload-');
        if ($filePath === false) {
            self::fail('File sementara untuk pengujian alias tidak dapat dibuat.');
        }

        try {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getActiveSheet()->fromArray([
                [
                    'No.',
                    'Nama',
                    'NIP',
                    'Status Jabatan',
                    'Pangkat',
                    'Golongan',
                    'TMT Golongan',
                    'Pendidikan',
                    'Tanggal Lulus',
                    'Gender',
                    'Tanggal Lahir',
                    'NIK',
                    null,
                    'Kolom Raw A',
                    'Kolom Raw B',
                    'Kolom Raw C',
                    'Masa Kerja',
                    'Pengingat SKS',
                    'Pengingat Pensiun',
                    'Pengingat KGB',
                    'No. WA',
                ],
                [
                    1,
                    'Pegawai Sintetis',
                    '198801152011011002',
                    'Fungsional',
                    'Penata',
                    'III/a',
                    '2021-04-01',
                    'S1 Statistika',
                    '2010-09-15',
                    'L',
                    '1988-01-15',
                    '3214011501880001',
                    null,
                    'RAW-A',
                    'RAW-B',
                    'RAW-C',
                    '10 Thn 3 Bln',
                    'Tidak ada',
                    '2028-01-15',
                    '2027-04-01',
                    null,
                ],
            ], null, 'A1');
            (new Xlsx($spreadsheet))->save($filePath);

            $uploadedFile = new UploadedFile(
                $filePath,
                'data_dummy_alias.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            );

            $uploadResponse = $this->actingAs($user)->post(route('batches.store'), [
                'validation_profile_id' => $profile->id,
                'file' => $uploadedFile,
            ]);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $uploadResponse->assertRedirect();
        $batch = ValidationBatch::query()->where('file_name', 'data_dummy_alias.xlsx')->firstOrFail();
        $this->assertSame('III/a', $batch->rows()->firstOrFail()->current_data[5]);
        $this->assertSame('S1 Statistika', $batch->rows()->firstOrFail()->current_data[7]);

        $this->post(route('validation.execute', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $uploadedRow = $batch->rows()->firstOrFail();
        $this->assertTrue($uploadedRow->refresh()->has_anomalies);
        $this->assertDatabaseHas('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $uploadedRow->id,
            'column_name' => 'No. WA',
            'column_index' => 20,
            'invalid_value' => 'null',
            'is_resolved' => false,
        ]);
        $this->assertSame(1, $batch->refresh()->summary['anomalous_rows']);
        $this->assertSame(1, $batch->summary['total_anomalies']);
        $this->assertSame(0, $batch->summary['valid_rows']);

        $results = $this->get(route('validation.results', $batch));
        $results->assertOk();
        $results->assertViewHas('stats', function (array $stats): bool {
            return $stats['valid_rows'] === 0
                && $stats['anomalous_rows'] === 1
                && $stats['total_anomalies'] === 1;
        });
        $results->assertViewHas('informationSummary', function (array $summary): bool {
            return $summary['total_rows'] === 1
                && $summary['gender']['male'] === 1
                && $summary['rank']['categories'] === [['label' => 'III/a', 'count' => 1]]
                && $summary['education']['categories'] === [['label' => 'S1 Statistika', 'count' => 1]]
                && $summary['age']['count'] === 1
                && $summary['tenure']['count'] === 1
                && $summary['retirement']['categories'] === [['label' => '2028-01-15', 'count' => 1]]
                && $summary['kgb']['categories'] === [['label' => '2027-04-01', 'count' => 1]];
        });
    }

    public function test_upload_rejects_duplicate_headers_for_the_same_standard_field(): void
    {
        Storage::fake('local');
        $profile = ValidationProfile::create([
            'name' => 'Data Kepegawaian',
            'slug' => 'data-kepegawaian',
            'is_active' => true,
        ]);
        $user = User::create([
            'name' => 'Petugas Header Ganda',
            'email' => 'petugas-header-ganda@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $filePath = tempnam(sys_get_temp_dir(), 'bps-duplicate-header-');
        if ($filePath === false) {
            self::fail('File sementara untuk pengujian header ganda tidak dapat dibuat.');
        }

        try {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getActiveSheet()->fromArray([
                ['Nama', 'NIP', 'Pendidikan', 'Pendidikan (SK)'],
                ['Pegawai Sintetis', '123456789012345678', 'D3', 'S1'],
            ], null, 'A1');
            (new Xlsx($spreadsheet))->save($filePath);

            $uploadedFile = new UploadedFile(
                $filePath,
                'data_header_ganda.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            );

            $response = $this->actingAs($user)->from(route('batches.create'))->post(route('batches.store'), [
                'validation_profile_id' => $profile->id,
                'file' => $uploadedFile,
            ]);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $response->assertRedirect(route('batches.create'));
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('validation_batches', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('excel_uploads'));
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
        $this->assertSame('198801152011011002', $batch->rows()->first()->current_data[2]);
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
