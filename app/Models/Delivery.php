<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
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
        'pekerjaan_istri',
        'pekerjaan_suami',
        'keluhan',
        'tindakan',
        'bayi_lahir',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'umur_istri' => 'integer',
        'umur_suami' => 'integer',
        'bayi_lahir' => 'boolean',
    ];

    /**
     * Get the user who created the delivery record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
