<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ujian;
use App\Models\Guru;

class HasilUjianController extends Controller
{
    public function getCompletedUjians(Request $request)
    {
        $userId = $request->query('user_id'); 

        try {
            $guru = Guru::where('userId', $userId)->first();

            if (!$guru) {
                return response()->json(['error' => 'Guru tidak ditemukan'], 404);
            }

            $completedUjians = Ujian::where('guru_id', $guru->guru_id)
                ->where('status_ujian', 'BERAKHIR')
                ->withCount(['peserta_ujians', 'soal_ujians'])
                ->with(['peserta_ujians' => function($query) {
                    $query->whereIn('status_ujian', ['SELESAI', 'DINILAI'])
                          ->with(['hasil_ujians', 'siswas']);
                }])
                ->orderBy('tanggal_selesai', 'desc')
                ->get();

            $formattedUjians = $completedUjians->map(function ($ujian) {
                return [
                    'ujian_id' => $ujian->ujian_id,
                    'nama_ujian' => $ujian->nama_ujian,
                    'mata_pelajaran' => $ujian->mata_pelajaran,
                    'tingkat' => $ujian->tingkat,
                    'jurusan' => $ujian->jurusan,
                    'tanggal_selesai' => $ujian->tanggal_selesai,
                    'statistics' => [
                        'total_peserta' => $ujian->peserta_ujians_count,
                        'total_selesai' => $ujian->peserta_ujians->count(),
                        'total_soal' => $ujian->soal_ujians_count,
                    ],
                    'peserta_results' => $ujian->peserta_ujians->map(fn($p) => [
                        'peserta_ujian_id' => $p->peserta_ujian_id,
                        'siswa' => [
                            'siswa_id' => $p->siswas->siswa_id,
                            'nama_lengkap' => $p->siswas->nama_lengkap,
                            'kelas' => $p->siswas->kelas,
                        ],
                        'status_ujian' => $p->status_ujian,
                        'nilai_akhir' => $p->hasil_ujians->nilai_akhir ?? null,
                        'tanggal_submit' => $p->hasil_ujians->tanggal_submit ?? null,
                    ]),
                ];
            });

            return response()->json([
                'total_ujian_selesai' => $formattedUjians->count(),
                'ujians' => $formattedUjians,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}