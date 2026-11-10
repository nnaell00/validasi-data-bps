<?php

namespace Tests\Feature;

use App\Models\BatchRow;
use App\Models\RowAnomaly;
use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use App\Services\DummyDataGenerator;
use App\Services\ExcelDataParser;
use Database\Seeders\ValidationProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationRevalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_rank_and_grade_exemptions_and_required_whatsapp_survive_revalidation(): void
    {
        $user = $this->createPetugas();
        [$batch, $rows] = $this->createDummyBatch($user);
        $currentData = $rows[0]->current_data;
        $currentData[3] = '  oPeRaToR   LaYaNaN OpErAsIoNaL ';
        $currentData[4] = '';
        $currentData[5] = null;
        $currentData[19] = '';
        $rows[0]->update(['current_data' => $currentData]);

        $editResponse = $this->actingAs($user)->get(route('validation.edit-row', [$batch, $rows[0]]));
        $editResponse->assertOk();
        $editResponse->assertSee('Wajib');
        $editResponse->assertDontSee('Opsional');

        $this->actingAs($user)
            ->post(route('validation.execute', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $this->assertDatabaseHas('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[0]->id,
            'column_name' => 'No. WA',
            'invalid_value' => '',
            'is_resolved' => false,
        ]);
        $this->assertDatabaseMissing('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[0]->id,
            'column_name' => 'Pangkat',
        ]);
        $this->assertDatabaseMissing('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[0]->id,
            'column_name' => 'Gol.Akhir',
        ]);

        $this->put(route('validation.update-row', [$batch, $rows[0]]), [
            'data' => [19 => '081234567890'],
        ])->assertRedirect(route('validation.results', $batch));

        $this->post(route('validation.revalidate', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $this->assertDatabaseMissing('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[0]->id,
            'column_name' => 'No. WA',
        ]);
        $this->assertFalse($rows[0]->refresh()->has_anomalies);
        $this->assertSame('081234567890', $rows[0]->current_data[19]);

        $this->put(route('validation.update-row', [$batch, $rows[0]]), [
            'data' => [19 => '   '],
        ])->assertRedirect(route('validation.results', $batch));

        $this->post(route('validation.revalidate', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $this->assertDatabaseHas('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[0]->id,
            'column_name' => 'No. WA',
            'invalid_value' => 'null',
            'is_resolved' => false,
        ]);
        $this->assertTrue($rows[0]->refresh()->has_anomalies);
    }

    public function test_revalidation_rechecks_all_27_rows_and_keeps_unfixed_anomalies(): void
    {
        $user = $this->createPetugas();
        [$batch, $rows] = $this->createDummyBatch($user);

        $this->actingAs($user)
            ->post(route('validation.execute', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $this->assertSame('validated', $batch->refresh()->status);
        $this->assertSame(17, $batch->summary['valid_rows']);
        $this->assertSame(10, $batch->summary['anomalous_rows']);
        $this->assertSame(14, $batch->summary['total_anomalies']);

        $initialResults = $this->get(route('validation.results', $batch));
        $initialResults->assertOk();
        $initialResults->assertSee('Ringkasan Informasi Data Kepegawaian');
        $initialResults->assertViewHas('informationSummary', function (array $summary): bool {
            return $summary['total_rows'] === 27
                && $summary['gender']['male'] === 21
                && $summary['gender']['female'] === 5
                && $summary['gender']['unrecognized_categories'] === [['label' => 'Bukan-L-P', 'count' => 1]]
                && collect($summary['rank']['categories'])->contains('label', 'V/A')
                && $summary['education']['categories'] !== []
                && $summary['age']['count'] === 26
                && $summary['tenure']['count'] === 27
                && $summary['retirement']['calculated'] === false;
        });

        $this->put(route('validation.update-row', [$batch, $rows[7]]), [
            'data' => [2 => '198701012010011001'],
        ])->assertRedirect(route('validation.results', $batch));

        $this->put(route('validation.update-row', [$batch, $rows[8]]), [
            'data' => [5 => 'III/a'],
        ])->assertRedirect(route('validation.results', $batch));

        $this->post(route('validation.revalidate', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $batch->refresh();
        $this->assertSame('validated', $batch->status);
        $this->assertSame(27, $batch->summary['total_rows']);
        $this->assertSame(18, $batch->summary['valid_rows']);
        $this->assertSame(9, $batch->summary['anomalous_rows']);
        $this->assertSame(12, $batch->summary['total_anomalies']);
        $this->assertFalse($rows[7]->refresh()->has_anomalies);
        $this->assertTrue($rows[8]->refresh()->has_anomalies);
        $this->assertTrue($rows[9]->refresh()->has_anomalies);
        $this->assertDatabaseHas('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[8]->id,
            'column_name' => 'No. WA',
            'is_resolved' => false,
        ]);
        $this->assertDatabaseHas('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[9]->id,
            'column_name' => 'Nama',
            'is_resolved' => false,
        ]);
        $revalidatedResults = $this->get(route('validation.results', $batch));
        $revalidatedResults->assertOk();
        $revalidatedResults->assertViewHas('informationSummary', function (array $summary): bool {
            return $summary['total_rows'] === 27
                && $summary['gender']['male'] === 21
                && ! collect($summary['rank']['categories'])->contains('label', 'V/A')
                && collect($summary['rank']['categories'])->contains('label', 'III/a')
                && $summary['age']['count'] === 26;
        });

        $this->assertSame(18, $batch->rows()->where('has_anomalies', false)->count());
        $this->assertSame(9, $batch->rows()->where('has_anomalies', true)->count());
        $this->assertSame(12, $batch->anomalies()->where('is_resolved', false)->count());
        $this->assertSame(
            0,
            $batch->rows()
                ->where('has_anomalies', true)
                ->whereDoesntHave('unresolvedAnomalies')
                ->count()
        );
        $this->assertSame(
            0,
            $batch->rows()
                ->where('has_anomalies', false)
                ->whereHas('unresolvedAnomalies')
                ->count()
        );

        $resultsResponse = $this->get(route('validation.results', $batch));
        $resultsResponse->assertOk();
        $resultsResponse->assertViewHas('stats', function (array $stats): bool {
            return $stats['total_rows'] === 27
                && $stats['valid_rows'] === 18
                && $stats['anomalous_rows'] === 9
                && $stats['total_anomalies'] === 12;
        });

        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertViewHas('stats', function (array $stats): bool {
            return $stats['processed_batches'] === 1
                && $stats['valid_rows'] === 18
                && $stats['anomalous_rows'] === 9
                && $stats['follow_up_rows'] === 9;
        });

        $historyResponse = $this->get(route('batches.index'));
        $historyResponse->assertOk();
        $historyResponse->assertSee('Sudah Divalidasi');
    }

    public function test_revalidation_keeps_a_corrected_but_still_invalid_value_anomalous(): void
    {
        $user = $this->createPetugas();
        [$batch, $rows] = $this->createDummyBatch($user);

        $this->actingAs($user)
            ->post(route('validation.execute', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $this->put(route('validation.update-row', [$batch, $rows[7]]), [
            'data' => [2 => '12345'],
        ])->assertRedirect(route('validation.results', $batch));

        $this->post(route('validation.revalidate', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $batch->refresh();
        $this->assertSame('validated', $batch->status);
        $this->assertSame(17, $batch->summary['valid_rows']);
        $this->assertSame(10, $batch->summary['anomalous_rows']);
        $this->assertSame(14, $batch->summary['total_anomalies']);
        $this->assertTrue($rows[7]->refresh()->is_corrected);
        $this->assertTrue($rows[7]->has_anomalies);
        $this->assertDatabaseHas('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $rows[7]->id,
            'column_name' => 'NIP',
            'invalid_value' => '12345',
            'is_resolved' => false,
        ]);
    }

    public function test_revalidation_keeps_previous_results_when_saving_new_anomalies_fails(): void
    {
        $user = $this->createPetugas();
        [$batch] = $this->createDummyBatch($user);

        $this->actingAs($user)
            ->post(route('validation.execute', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $originalSummary = $batch->refresh()->summary;
        $originalAnomalyCount = $batch->anomalies()->count();
        $rejectAnomalyCreation = false;
        RowAnomaly::creating(function () use (&$rejectAnomalyCreation): void {
            if ($rejectAnomalyCreation) {
                throw new \RuntimeException('Simulasi kegagalan penyimpanan temuan validasi.');
            }
        });
        $rejectAnomalyCreation = true;

        $this->post(route('validation.revalidate', $batch))->assertStatus(500);

        $this->assertSame('validated', $batch->refresh()->status);
        $this->assertSame($originalSummary, $batch->summary);
        $this->assertSame($originalAnomalyCount, $batch->anomalies()->count());
        $this->assertSame(10, $batch->rows()->where('has_anomalies', true)->count());
    }

    private function createPetugas(): User
    {
        return User::create([
            'name' => 'Petugas Revalidasi',
            'email' => 'petugas-revalidasi@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: ValidationBatch, 1: array<int, BatchRow>}
     */
    private function createDummyBatch(User $user): array
    {
        $this->seed(ValidationProfileSeeder::class);
        $profile = ValidationProfile::query()->where('slug', 'data-kepegawaian')->firstOrFail();
        $filePath = (new DummyDataGenerator)->generate();

        try {
            $parsed = (new ExcelDataParser)->parse($filePath);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $dataRows = $parsed['rows'];
        for ($rowIndex = count($dataRows); $rowIndex < 27; $rowIndex++) {
            $duplicate = $dataRows[0];
            $duplicate[0] = $rowIndex + 1;
            $dataRows[] = $duplicate;
        }

        $batch = ValidationBatch::create([
            'user_id' => $user->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'data_dummy_27_baris.xlsx',
            'file_path' => 'synthetic/data_dummy_27_baris.xlsx',
            'total_rows' => count($dataRows),
            'status' => 'uploaded',
            'headers' => $parsed['headers'],
        ]);

        $rows = [];
        foreach ($dataRows as $rowIndex => $data) {
            $rows[] = BatchRow::create([
                'validation_batch_id' => $batch->id,
                'row_index' => $rowIndex,
                'row_number' => $rowIndex + 1,
                'original_data' => $data,
                'current_data' => $data,
            ]);
        }

        return [$batch, $rows];
    }
}
