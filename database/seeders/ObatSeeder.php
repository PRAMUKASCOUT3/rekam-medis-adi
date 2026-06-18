<?php

namespace Database\Seeders;

use App\Models\Obat;
use Illuminate\Database\Seeder;
use Faker\Factory;

class ObatSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('id_ID');

        $namaObat = [
            'Paracetamol 500mg',
            'Amoxicillin 500mg',
            'Ibuprofen 400mg',
            'Omeprazole 20mg',
            'Cetirizine 10mg',
            'Salbutamol 2mg',
            'Dexamethasone 0.5mg',
            'Metformin 500mg',
            'Amlodipine 5mg',
            'Antasida Sirup',
        ];

        $typeList = ['tablet', 'kapsul', 'tablet', 'kapsul', 'tablet', 'tablet', 'tablet', 'tablet', 'tablet', 'sirup'];
        $satuanList = ['Strip', 'Strip', 'Strip', 'Strip', 'Strip', 'Strip', 'Strip', 'Strip', 'Strip', 'Botol'];

        foreach ($namaObat as $index => $nama) {
            Obat::create([
                'kode' => 'OBT' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'nama' => $nama,
                'type' => $typeList[$index],
                'satuan' => $satuanList[$index],
                'stok' => $faker->numberBetween(10, 500),
            ]);
        }
    }
}
