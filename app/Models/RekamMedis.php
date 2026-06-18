<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RekamMedis extends Model
{

    protected $table = 'rekam_medis';
    protected $primaryKey = 'id';
    protected $softDeletes = true;
    protected $fillable = [
        'obat_id',
        'pasien_id',
        'user_id',
        'nomor_rekam_medis',
        'tanggal_pemeriksaan',
        'keluhan',
        'diagnosa',
        'catatan',
        'resep_obat',
        'tekanan_darah',
        'suhu_tubuh',
        'berat_badan',
        'tinggi_badan',
        'detak_jantung',
        'laju_pernapasan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_pemeriksaan' => 'datetime',
        ];
    }

    /**
     * Get the patient associated with the medical record.
     */
    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    /**
     * Get the drug/medicine associated with the medical record (legacy single).
     */
    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    /**
     * Get all drugs prescribed in the medical record (multiple).
     */
    public function obats(): BelongsToMany
    {
        return $this->belongsToMany(Obat::class)
            ->withPivot(['jumlah', 'dosis', 'catatan'])
            ->withTimestamps();
    }

    /**
     * Get the user who created the medical record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
