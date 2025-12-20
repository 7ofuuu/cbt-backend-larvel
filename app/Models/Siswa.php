<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'siswas';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'siswa_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama_lengkap',
        'kelas',
        'tingkat',
        'jurusan',
        'userId',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the user that owns the siswa profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
