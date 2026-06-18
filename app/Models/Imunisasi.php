<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Imunisasi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'imunisasis';
    protected $primaryKey = 'id';
    protected $fillable = [
        'pasien_id',
        'user_id',
        'tanggal_imunisasi',
        'jenis_imunisasi',
        'tanggal_lahir_pasien',
        'nama_orang_tua',
        'alamat',
        'pengobatan',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_imunisasi' => 'date',
    ];

    /**
     * Get the pasien associated with the imunisasi.
     */
    public function pasien()
    {
        return $this->belongsTo(Pasien::class);
    }

    /**
     * Get the user who created the imunisasi record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
