<?php

namespace Database\Seeders;

use App\Models\Pasien;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory;

class PasienSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('id_ID');

        $jenisPasienOptions = ['bayi', 'anak-anak', 'dewasa'];
        $jenisKelaminOptions = ['L', 'P'];
        $golonganDarahOptions = ['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        for ($i = 0; $i < 10; $i++) {
            $jenisPasien = $faker->randomElement($jenisPasienOptions);
            $jenisKelamin = $faker->randomElement($jenisKelaminOptions);
            $golonganDarah = $faker->randomElement($golonganDarahOptions);
            $tanggalLahir = match ($jenisPasien) {
                'bayi' => $faker->dateTimeBetween('-1 years', 'now'),
                'anak-anak' => $faker->dateTimeBetween('-12 years', '-1 years'),
                default => $faker->dateTimeBetween('-70 years', '-13 years'),
            };

            Pasien::create([
                'nama' => $faker->name,
                'jenis_pasien' => $jenisPasien,
                'nik' => $faker->unique()->numerify('################'),
                'no_telpon' => $faker->numerify('08##########'),
                'alamat' => $faker->address,
                'tanggal_lahir' => $tanggalLahir->format('Y-m-d'),
                'jenis_kelamin' => $jenisKelamin,
                'golongan_darah' => $golonganDarah,
                'alergi' => $faker->optional()->randomElement(['-', 'Penisilin', 'Sulfa', 'Ketan', 'Seafood']),
            ]);
        }
    }
}
