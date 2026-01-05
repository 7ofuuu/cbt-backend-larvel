<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoalUjian extends Model
{
    protected $table = 'soal_ujians';
    protected $primaryKey = 'soal_ujian_id';
    public $timestamps = false;

    public function ujians()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id', 'ujian_id');
    }

    public function soals()
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'soal_id');
    }
}