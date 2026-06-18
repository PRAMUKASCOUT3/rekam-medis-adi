<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kb extends Model
{
    use SoftDeletes;

    protected $table = 'kb';

    protected $fillable = [
        'user_id',
        'tanggal',
        'no_regis',
        'nama_istri',
        'nama_suami',
        'umur_istri',
        'alamat',
        'nik_istri',
        'no_hp',
        'tekanan_darah',
        'berat_badan',
        'metode_kb',
        'tanggal_kunjungan',
        'tanggal_kunjungan_ulang',
        'keluhan',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_kunjungan' => 'date',
        'tanggal_kunjungan_ulang' => 'date',
        'umur_istri' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
