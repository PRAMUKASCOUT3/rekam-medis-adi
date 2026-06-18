<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    protected $table = 'obats';
    protected $primaryKey = 'id';
    protected $softDeletes = true;
    protected $fillable = ['kode', 'nama', 'type', 'satuan', 'stok'];
}
