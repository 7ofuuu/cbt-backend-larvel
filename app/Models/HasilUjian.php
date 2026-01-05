<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilUjian extends Model
{
    protected $table = 'hasil_ujians';
    protected $primaryKey = 'hasil_ujian_id';
    public $timestamps = false;

    public function peserta_ujians()
    {
        return $this->belongsTo(PesertaUjian::class, 'peserta_ujian_id', 'peserta_ujian_id');
    }
}