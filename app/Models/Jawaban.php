<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jawaban extends Model
{
    protected $table = 'jawabans';
    protected $primaryKey = 'jawaban_id';
    public $timestamps = false;

    public function soals()
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'soal_id');
    }

    public function peserta_ujians()
    {
        return $this->belongsTo(PesertaUjian::class, 'peserta_ujian_id', 'peserta_ujian_id');
    }
}
