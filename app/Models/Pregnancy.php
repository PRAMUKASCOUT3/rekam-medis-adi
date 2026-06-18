<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pregnancy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tanggal',
        'nama_istri',
        'nama_suami',
        'umur_istri',
        'umur_suami',
        'alamat',
        'no_telpon',
        'gravida',
        'partus',
        'abortus',
        'hpht',
        'tp',
        'pemeriksaan',
        'keluhan',
        'terapi',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'hpht' => 'date',
        'tp' => 'date',
        'umur_istri' => 'integer',
        'umur_suami' => 'integer',
        'gravida' => 'integer',
        'partus' => 'integer',
        'abortus' => 'integer',
    ];

    /**
     * Get the user who created the pregnancy record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
