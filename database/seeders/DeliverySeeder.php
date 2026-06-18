<?php

namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('id_ID');

        $userIds = User::where('role', 'user')->pluck('id')->all();

        if (empty($userIds)) {
            return;
        }

        $pekerjaanIstriList = ['Ibu Rumah Tangga', 'Pegawai Swasta', 'Wiraswasta', 'Guru', 'Perawat'];
        $pekerjaanSuamiList = ['Karyawan Swasta', 'Wiraswasta', 'Pegawai Negeri', 'Supir', 'Buruh'];

        $keluhanList = [
            'Kontraksi hebat secara teratur',
            'Ketuban pecah dini',
            'Nyeri pinggul hebat',
            'Perdarahan ringan',
            'Sakit kepala hebat',
            'Mual dan muntah',
            'Sesak napas',
            'Pusing',
            'Nyeri perut bagian bawah',
            'Tidak ada keluhan khusus',
        ];

        $tindakanList = [
            'Persalinan normal dengan episiotomi',
            'Persalinan normal tanpa episiotomi',
            'Persalinan Caesar',
            'Forceps delivery',
            'Vacuum extraction',
            'Persalinan normal dengan助手',
        ];

        $keteranganList = [
            'Bayi lahir sehat, tidak ada kelainan',
            'Bayi lahir dengan berat normal',
            'Ibu dalam kondisi stabil',
            'Kontrol postnatal 1 minggu lagi',
            'RekomendasiASI eksklusif',
            'Tidak ada komplikasi',
        ];

        for ($i = 0; $i < 10; $i++) {
            $namaIstri = $faker->name('female');
            $namaSuami = $faker->optional(0.8)->name('male');
            $keluhan = $keluhanList[$i] ?? $faker->sentence(3);
            $tindakan = $tindakanList[$i] ?? 'Persalinan normal';
            $bayiLahir = $faker->boolean(85);

            Delivery::create([
                'user_id' => $faker->randomElement($userIds),
                'tanggal' => $faker->dateTimeBetween('-6 months', 'now'),
                'nama_istri' => $namaIstri,
                'nama_suami' => $namaSuami,
                'umur_istri' => $faker->numberBetween(18, 45),
                'umur_suami' => $namaSuami ? $faker->numberBetween(20, 60) : null,
                'alamat' => $faker->address,
                'no_telpon' => $faker->numerify('08##########'),
                'pekerjaan_istri' => $faker->randomElement($pekerjaanIstriList),
                'pekerjaan_suami' => $namaSuami ? $faker->randomElement($pekerjaanSuamiList) : null,
                'keluhan' => $keluhan,
                'tindakan' => $tindakan,
                'bayi_lahir' => $bayiLahir,
                'keterangan' => $faker->randomElement($keteranganList),
            ]);
        }
    }
}
