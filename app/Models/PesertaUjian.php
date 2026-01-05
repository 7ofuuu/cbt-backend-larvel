<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaUjian extends Model
{
    protected $table = 'peserta_ujians';
    protected $primaryKey = 'peserta_ujian_id';
    public $timestamps = false;

    public function siswas()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'siswa_id');
    }

    public function hasil_ujians()
    {
        return $this->hasOne(HasilUjian::class, 'peserta_ujian_id', 'peserta_ujian_id');
    }

    public function jawabans()
    {
        return $this->hasMany(\App\Models\Jawaban::class, 'peserta_ujian_id', 'peserta_ujian_id');
    }

    public function ujians()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id', 'ujian_id');
    }
}