<?php

namespace Database\Seeders;

use App\Models\Pasien;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(PasienSeeder::class);
        $this->call(ObatSeeder::class);
        $this->call(RekamMedisSeeder::class);
        $this->call(PregnancySeeder::class);
        $this->call(ImunisasiSeeder::class);
        $this->call(KbSeeder::class);
    }
}
}
