<?php

namespace Database\Seeders;

use App\Models\Imunisasi;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory;

class ImunisasiSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('id_ID');
        $pasienIds = Pasien::where('jenis_pasien', 'bayi')->pluck('id')->all();
        $userIds = User::where('role', 'user')->pluck('id')->all();

        $jenisImunisasiList = [
            'BCG',
            'Hepatitis B (HB 0)',
            'Polio 1',
            'DPT-HB-Hib 1',
            'Polio 2',
            'DPT-HB-Hib 2',
            'Polio 3',
            'DPT-HB-Hib 3',
            'Campak (MR)',
            'Booster DPT',
        ];

        if (!empty($pasienIds) && !empty($userIds)) {
            for ($i = 0; $i < 10; $i++) {
                Imunisasi::create([
                    'pasien_id' => $faker->randomElement($pasienIds),
                    'user_id' => $faker->randomElement($userIds),
                    'tanggal_imunisasi' => $faker->dateTimeBetween('-2 years', 'now'),
                    'jenis_imunisasi' => $jenisImunisasiList[$i],
                    'tanggal_lahir_pasien' => $faker->date('Y-m-d', '-2 years'),
                    'nama_orang_tua' => $faker->name,
                    'alamat' => $faker->address,
                    'pengobatan' => $faker->sentence(6),
                    'keterangan' => $faker->sentence(4),
                ]);
            }
        }
    }
}
