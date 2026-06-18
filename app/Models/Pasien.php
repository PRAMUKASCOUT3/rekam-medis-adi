<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    protected $table = 'pasiens';
    protected $softDeletes = true;
    protected $primary = 'id';
    protected $fillable = [
        'nama',
        'jenis_pasien',
        'nik',
        'no_telpon',
        'alamat',
        'tanggal_lahir',
        'jenis_kelamin',
        'golongan_darah',
        'alergi',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }
}
