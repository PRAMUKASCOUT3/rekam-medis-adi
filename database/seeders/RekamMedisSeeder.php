<?php

namespace Database\Seeders;

use App\Models\Obat;
use App\Models\Pasien;
use App\Models\RekamMedis;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory;

class RekamMedisSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('id_ID');

        $pasienIds = Pasien::query()->pluck('id')->all();
        $userIds = User::where('role', 'user')->pluck('id')->all();
        $obatIds = Obat::query()->pluck('id')->all();

        if (!empty($pasienIds) && !empty($userIds)) {
            $keluhanList = [
                'Demam tinggi sejak 2 hari lalu',
                'Sesak napas dan batuk berdahak',
                'Sakit kepala hebat',
                'Nyeri ulu hati',
                'Gatal-gatal di seluruh badan',
                'Nyeri sendi lutut kanan',
                'Mual dan muntah',
                'Pilek dan bersin-bersin',
                'Sakit tenggorokan',
                'Luka bakar ringan di tangan',
            ];

            $diagnosaList = [
                'ISPA',
                'Bronkitis',
                'Cedera Kepala Ringan',
                'Gastritis',
                'Dermatitis',
                'Artriti',
                'Gastroenteritis',
                'Rhinitis',
                'Faringitis',
                'Luka Bakar Derajat I',
            ];

            $catatanList = [
                'Istirahat yang cukup',
                'Perbanyak minum air putih',
                'Hindari makanan pedas',
                'Pantau suhu tubuh setiap 4 jam',
                'Kompres hangat',
                'Sterilisasi luka setiap 2 hari',
                'Kontrol ulang dalam 3 hari',
                'Tidak boleh memaksa aktivitas berat',
                'Jika demam naik tinggi segera ke UGD',
                'Jangan digesek/garuk',
            ];

            $resepObatList = [
                'Paracetamol 3x1 tablet',
                'Amoxicillin 3x1 kapsul',
                'Ibuprofen 2x1 tablet',
                'Omeprazole 1x1 kapsul',
                'Cetirizine 1x1 tablet',
                'Salbutamol 2x1 tablet',
                'Dexamethasone 1x1 tablet',
                'Metformin 2x1 tablet',
                'Amlodipine 1x1 tablet',
                'Antasida 3x1 sendok makan',
            ];

            for ($i = 0; $i < 10; $i++) {
                RekamMedis::updateOrCreate(
                    [
                        'pasien_id' => $faker->randomElement($pasienIds),
                        'user_id' => $faker->randomElement($userIds),
                        'nomor_rekam_medis' => 'RM' . now()->format('YmdHis') . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'obat_id' => $faker->randomElement($obatIds),
                        'tanggal_pemeriksaan' => $faker->dateTimeBetween('-30 days', 'now'),
                        'keluhan' => $keluhanList[$i] ?? 'Keluhan umum',
                        'diagnosa' => $diagnosaList[$i] ?? 'Menunggu diagnosis',
                        'catatan' => $catatanList[$i] ?? 'Tidak ada catatan',
                        'resep_obat' => $resepObatList[$i] ?? 'Tidak ada resep',
                        'tekanan_darah' => $faker->randomElement(['110/70', '120/80', '130/85', '90/60', '140/90']),
                        'suhu_tubuh' => $faker->randomFloat(1, 35, 38) . ' C',
                        'berat_badan' => $faker->numberBetween(40, 100) . ' kg',
                        'tinggi_badan' => $faker->numberBetween(145, 185) . ' cm',
                        'detak_jantung' => $faker->numberBetween(60, 110),
                        'laju_pernapasan' => $faker->numberBetween(12, 24),
                    ]
                );
            }
        }
    }
}
