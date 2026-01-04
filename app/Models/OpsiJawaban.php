<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpsiJawaban extends Model
{
    use HasFactory;

    protected $table = 'opsi_jawabans';
    protected $primaryKey = 'opsi_jawaban_id';

    protected $fillable = [
        'soal_id',
        'label',
        'teks_opsi',
        'is_benar',
    ];

    public $timestamps = false;

    public function soal()
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'soal_id');
    }
}
