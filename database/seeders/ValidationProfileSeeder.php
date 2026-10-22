<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ValidationProfile;
use App\Models\ValidationRule;
use Illuminate\Database\Seeder;

class ValidationProfileSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $profile = ValidationProfile::updateOrCreate(
            ['slug' => 'data-kepegawaian'],
            [
                'name' => 'Data Kepegawaian',
                'description' => 'Profil validasi data kepegawaian staf BPS Kabupaten Purwakarta dengan 18 kolom terdefinisi dan 3 kolom raw dipertahankan.',
                'is_active' => true,
                'created_by' => $admin?->id,
            ]
        );

        // Hapus aturan lama jika ada untuk refresh data
        $profile->rules()->delete();

        $rules = [
            [
                'column_name' => 'No.',
                'column_index' => 0,
                'data_type' => 'numeric',
                'is_required' => true,
                'validation_type' => 'numeric',
                'parameters' => ['min' => 1],
                'error_message' => 'Nomor urut harus berupa angka positif.',
                'needs_confirmation' => false,
                'confirmation_note' => null,
            ],
            [
                'column_name' => 'Nama',
                'column_index' => 1,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'none',
                'parameters' => ['min_length' => 2],
                'error_message' => 'Nama pegawai wajib diisi dan minimal 2 karakter.',
                'needs_confirmation' => false,
                'confirmation_note' => null,
            ],
            [
                'column_name' => 'NIP',
                'column_index' => 2,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'regex',
                'parameters' => ['pattern' => '/^[0-9]{18}$/D'],
                'error_message' => 'NIP harus terdiri tepat dari 18 digit angka.',
                'needs_confirmation' => true,
                'confirmation_note' => 'NIP tidak sesuai format yang didukung. Verifikasi nilai sumber sebelum menentukan perbaikan.',
            ],
            [
                'column_name' => 'Stat.Jabatan',
                'column_index' => 3,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'none',
                'parameters' => null,
                'error_message' => 'Status jabatan wajib diisi.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi daftar baku status jabatan resmi pada BPS Kabupaten Purwakarta (misal: Fungsional Tertentu, Struktural, Pelaksana).',
            ],
            [
                'column_name' => 'Pangkat',
                'column_index' => 4,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'none',
                'parameters' => null,
                'error_message' => 'Pangkat pegawai wajib diisi.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi apakah nama pangkat harus persis sesuai nomenklatur ASN (misal: Penata Muda, Pembina, dll.).',
            ],
            [
                'column_name' => 'Gol.Akhir',
                'column_index' => 5,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'regex',
                'parameters' => ['pattern' => '/^(I|II|III|IV)\/[a-e]$/i'],
                'error_message' => 'Format Golongan Akhir harus valid (contoh: III/a, III/b, IV/a).',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi rentang golongan ASN yang berlaku pada BPS Purwakarta.',
            ],
            [
                'column_name' => 'TMT Gol.',
                'column_index' => 6,
                'data_type' => 'date',
                'is_required' => true,
                'validation_type' => 'date_format',
                'parameters' => ['format' => 'Y-m-d|d/m/Y|Y/m/d'],
                'error_message' => 'TMT Golongan harus berupa tanggal yang valid.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi format input tanggal baku di Excel internal BPS.',
            ],
            [
                'column_name' => 'Pendidikan (SK)',
                'column_index' => 7,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'none',
                'parameters' => null,
                'error_message' => 'Pendidikan SK wajib diisi.',
                'needs_confirmation' => false,
                'confirmation_note' => null,
            ],
            [
                'column_name' => 'Tgl.Lulus (SK)',
                'column_index' => 8,
                'data_type' => 'date',
                'is_required' => true,
                'validation_type' => 'date_format',
                'parameters' => ['format' => 'Y-m-d|d/m/Y|Y/m/d'],
                'error_message' => 'Tanggal kelulusan SK harus berupa tanggal yang valid.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi format tanggal kelulusan.',
            ],
            [
                'column_name' => 'Jenis Kelamin',
                'column_index' => 9,
                'data_type' => 'enum',
                'is_required' => true,
                'validation_type' => 'in_list',
                'parameters' => ['options' => ['L', 'LK', 'Laki-laki', 'P', 'PR', 'Perempuan', 'Wanita']],
                'error_message' => 'Jenis kelamin harus L, LK, Laki-laki, P, PR, Perempuan, atau Wanita.',
                'needs_confirmation' => false,
                'confirmation_note' => null,
            ],
            [
                'column_name' => 'Tgl.Lahir',
                'column_index' => 10,
                'data_type' => 'date',
                'is_required' => true,
                'validation_type' => 'date_format',
                'parameters' => ['format' => 'Y-m-d|d/m/Y|Y/m/d'],
                'error_message' => 'Tanggal lahir harus berupa tanggal yang valid.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi konsistensi tanggal lahir terhadap 8 digit pertama NIP.',
            ],
            [
                'column_name' => 'NIK',
                'column_index' => 11,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'regex',
                'parameters' => ['pattern' => '/^\d{16}$/'],
                'error_message' => 'NIK harus terdiri tepat 16 digit angka.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi apakah NIK seluruh pegawai BPS wajib terisi lengkap 16 digit angka.',
            ],
            // Catatan: Kolom 12, 13, 14 adalah 3 kolom yang belum diketahui namanya.
            // Sesuai aturan khusus, kolom ini diabaikan dari penalti/anomali, namun tetap disimpan utuh saat import/ekspor.
            [
                'column_name' => 'MK',
                'column_index' => 15,
                'data_type' => 'string',
                'is_required' => true,
                'validation_type' => 'none',
                'parameters' => null,
                'error_message' => 'Masa Kerja (MK) wajib terisi.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi format pencatatan Masa Kerja (angka murni tahun, bulan, atau teks misal "10 Th 2 Bln").',
            ],
            [
                'column_name' => 'Pengingat SKS',
                'column_index' => 16,
                'data_type' => 'string',
                'is_required' => false,
                'validation_type' => 'none',
                'parameters' => null,
                'error_message' => 'Format Pengingat SKS tidak valid.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi tipe nilai pengingat SKS (apakah tanggal jatuh tempo, angka semester/sks, atau notifikasi teks).',
            ],
            [
                'column_name' => 'Pengingat Pensiun',
                'column_index' => 17,
                'data_type' => 'string',
                'is_required' => false,
                'validation_type' => 'none',
                'parameters' => null,
                'error_message' => 'Format Pengingat Pensiun tidak valid.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi tipe pengingat pensiun (tanggal TMT pensiun atau selisih sisa tahun).',
            ],
            [
                'column_name' => 'Pengingat KGB',
                'column_index' => 18,
                'data_type' => 'string',
                'is_required' => false,
                'validation_type' => 'none',
                'parameters' => null,
                'error_message' => 'Format Pengingat Kenaikan Gaji Berkala (KGB) tidak valid.',
                'needs_confirmation' => true,
                'confirmation_note' => 'Perlu konfirmasi tipe pengingat KGB (tanggal periode KGB berikutnya atau status).',
            ],
            [
                'column_name' => 'No. WA',
                'column_index' => 19,
                'data_type' => 'string',
                'is_required' => false, // Aturan khusus: No. WA opsional! Nilai kosong bukan anomali.
                'validation_type' => 'phone',
                'parameters' => ['pattern' => '/^(\+?62|08)\d{8,13}$/'],
                'error_message' => 'Nomor WhatsApp jika diisi harus berformat nomor Indonesia yang valid (contoh: 08123456789 atau 628123456789).',
                'needs_confirmation' => false,
                'confirmation_note' => 'Aturan khusus terpenuhi: Bersifat opsional, nilai kosong diizinkan.',
            ],
        ];

        foreach ($rules as $ruleData) {
            ValidationRule::create(array_merge($ruleData, [
                'validation_profile_id' => $profile->id,
                'is_active' => true,
            ]));
        }
    }
}
