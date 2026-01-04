<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ujian extends Model
{
    protected $table = 'ujians';
    protected $primaryKey = 'ujian_id';
    public $timestamps = false;

    public function peserta_ujians()
    {
        return $this->hasMany(PesertaUjian::class, 'ujian_id', 'ujian_id');
    }

    public function soal_ujians()
    {
        return $this->hasMany(SoalUjian::class, 'ujian_id', 'ujian_id');
    }

    public function gurus()
    {
        return $this->belongsTo(Guru::class, 'guru_id', 'guru_id');
    }
}