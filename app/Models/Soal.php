<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Soal extends Model
{
    protected $table = 'soals';
    protected $primaryKey = 'soal_id';
    public $incrementing = true;

    protected $fillable = [
        'tipe_soal',
        'teks_soal',
        'mata_pelajaran',
        'tingkat',
        'jurusan',
        'soal_gambar',
        'soal_pembahasan',
        'guru_id',
    ];

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    public function opsiJawabans()
    {
        return $this->hasMany(OpsiJawaban::class, 'soal_id', 'soal_id');
    }

    public function soalUjians()
    {
        return $this->hasMany(SoalUjian::class, 'soal_id', 'soal_id');
    }
}
