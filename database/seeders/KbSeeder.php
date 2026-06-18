<?php

namespace Database\Seeders;

use App\Models\Kb;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory;

class KbSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('id_ID');

        $userIds = User::where('role', 'user')->pluck('id')->all();

        $metodeKbList = [
            'Pil',
            'Suntik',
            'IUD',
            'Implan',
            'Kondom',
            'MOP',
            'MOW',
            'Dewasa',
        ];

        $keluhanList = [
            'Tidak ada keluhan',
            'Pusing ringan setelah pil',
            'Nyeri pada bagian perut',
            'Perdarahan antar haid',
            'Keputihan',
            'Gangguan tidur',
            'Sakit kepala',
            'Nyeri payudara',
            'Mual ringan',
            'Lemas',
        ];

        $keteranganList = [
            'Kontrol rutin',
            'Suntik ulang',
            'Ganti implan',
            'Cek kembali',
            'Tidak ada komplikasi',
            'Kopiing',
            'Pemeriksaan ulang',
        ];

        if (empty($userIds)) {
            return;
        }

        for ($i = 0; $i < 10; $i++) {
            $tanggalKunjungan = $faker->dateTimeBetween('-3 months', 'now');
            $tanggalKunjunganUlang = $faker->optional(0.7)->dateTimeBetween('+1 month', '+6 months');

            Kb::create([
                'user_id' => $faker->randomElement($userIds),
                'tanggal' => $faker->dateTimeBetween('-1 year', 'now'),
                'no_regis' => 'KB' . now()->format('Ymd') . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'nama_istri' => $faker->name('female'),
                'nama_suami' => $faker->optional(0.9)->name('male'),
                'umur_istri' => $faker->numberBetween(18, 45),
                'alamat' => $faker->address,
                'nik_istri' => $faker->numerify('################'),
                'no_hp' => $faker->numerify('08##########'),
                'tekanan_darah' => $faker->randomElement(['110/70', '120/80', '130/85', '90/60', '140/90']),
                'berat_badan' => $faker->numberBetween(45, 85) . ' kg',
                'metode_kb' => $faker->randomElement($metodeKbList),
                'tanggal_kunjungan' => $tanggalKunjungan,
                'tanggal_kunjungan_ulang' => $tanggalKunjunganUlang,
                'keluhan' => $faker->randomElement($keluhanList),
                'keterangan' => $faker->randomElement($keteranganList),
            ]);
        }
    }
}
