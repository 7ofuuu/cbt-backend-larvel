<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\PesertaUjian;
use App\Models\Jawaban;
use App\Models\HasilUjian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SiswaController extends Controller
{
    public function getMyUjians(Request $request)
    {
        $user = $request->user();
        $siswa = Siswa::where('userId', $user->id)->first();
        if (! $siswa) {
            return response()->json(['error' => 'Siswa tidak ditemukan'], 404);
        }

        $peserta = PesertaUjian::where('siswa_id', $siswa->siswa_id)
            ->whereHas('ujians', function ($q) {
                $q->whereIn('status_ujian', ['TERJADWAL', 'BERLANGSUNG']);
            })
            ->with(['ujians.soal_ujians.soals.opsiJawabans', 'hasil_ujians', 'ujians'])
            ->get()
            ->sortByDesc(function ($p) {
                return optional($p->ujians)->tanggal_mulai;
            })
            ->values();

        $result = $peserta->map(function ($pu) {
            return [
                'peserta_ujian_id' => $pu->peserta_ujian_id,
                'status_ujian' => $pu->status_ujian,
                'is_blocked' => (bool) $pu->is_blocked,
                'unlock_code' => $pu->unlock_code,
                'waktu_mulai' => $pu->waktu_mulai,
                'waktu_selesai' => $pu->waktu_selesai,
                'ujian' => $pu->ujians,
                'hasil' => $pu->hasil_ujians,
            ];
        });

        return response()->json(['ujians' => $result]);
    }

    public function startUjian(Request $request)
    {
        $user = $request->user();
        $siswa = Siswa::where('userId', $user->id)->first();
        if (! $siswa) {
            return response()->json(['error' => 'Siswa tidak ditemukan'], 404);
        }

        $pesertaId = (int) $request->input('peserta_ujian_id');
        $unlockCode = $request->input('unlock_code');

        $peserta = PesertaUjian::with('ujians.soal_ujians.soals.opsiJawabans')
            ->where('peserta_ujian_id', $pesertaId)
            ->where('siswa_id', $siswa->siswa_id)
            ->first();

        if (! $peserta) {
            return response()->json(['error' => 'Peserta ujian tidak ditemukan'], 404);
        }

        if ($peserta->is_blocked) {
            if (! $unlockCode || $unlockCode !== $peserta->unlock_code) {
                return response()->json([
                    'error' => 'Ujian terblokir. Silakan minta kode unlock dari pengawas.',
                    'is_blocked' => true,
                ], 403);
            }

            $peserta->is_blocked = false;
            $peserta->unlock_code = null;
            $peserta->save();
        }

        if (in_array($peserta->status_ujian, ['SELESAI', 'DINILAI'])) {
            return response()->json(['error' => 'Ujian sudah selesai dikerjakan', 'status' => $peserta->status_ujian], 400);
        }

        $now = Carbon::now();
        $tanggalMulai = $peserta->ujians ? Carbon::parse($peserta->ujians->tanggal_mulai) : null;
        $tanggalSelesai = $peserta->ujians ? Carbon::parse($peserta->ujians->tanggal_selesai) : null;

        if ($tanggalMulai && $now->lt($tanggalMulai)) {
            return response()->json(['error' => 'Ujian belum dimulai', 'tanggal_mulai' => $tanggalMulai], 400);
        }
        if ($tanggalSelesai && $now->gt($tanggalSelesai)) {
            return response()->json(['error' => 'Waktu ujian sudah berakhir', 'tanggal_selesai' => $tanggalSelesai], 400);
        }

        if ($peserta->status_ujian === 'BELUM_MULAI') {
            $peserta->status_ujian = 'SEDANG_DIKERJAKAN';
            $peserta->waktu_mulai = $now;
            $peserta->save();
            $peserta->refresh();
            $peserta->load('ujians.soal_ujians.soals.opsiJawabans');
        }

        $existingJawabans = Jawaban::where('peserta_ujian_id', $peserta->peserta_ujian_id)->get();

        Log::info('START UJIAN', ['ujian_id' => optional($peserta->ujians)->ujian_id, 'total_soal' => optional($peserta->ujians->soal_ujians)->count()]);

        $soalList = collect($peserta->ujians->soal_ujians ?? [])->map(function ($su) use ($existingJawabans) {
            $jawaban = $existingJawabans->firstWhere('soal_id', $su->soal_id);
            $isPilihanGanda = in_array(optional($su->soals)->tipe_soal, ['PILIHAN_GANDA', 'PILIHAN_GANDA_SINGLE', 'PILIHAN_GANDA_MULTIPLE']);

            return [
                'soal_ujian_id' => $su->soal_ujian_id,
                'urutan' => $su->urutan,
                'bobot_nilai' => $su->bobot_nilai,
                'soal' => [
                    'soal_id' => optional($su->soals)->soal_id,
                    'tipe_soal' => optional($su->soals)->tipe_soal,
                    'teks_soal' => optional($su->soals)->teks_soal,
                    'soal_gambar' => optional($su->soals)->soal_gambar,
                    'opsi_jawaban' => $isPilihanGanda && optional($su->soals)->opsiJawabans ? collect($su->soals->opsiJawabans)->map(function ($opsi) {
                        return [
                            'opsi_id' => $opsi->opsi_jawaban_id,
                            'label_opsi' => $opsi->label ?? $opsi->label_opsi ?? null,
                            'teks_opsi' => $opsi->teks_opsi,
                        ];
                    })->values() : [],
                ],
                'jawaban_saya' => $jawaban ? [
                    'jawaban_id' => $jawaban->jawaban_id,
                    'opsi_jawaban_id' => $jawaban->jawaban_pg_opsi_ids ? (int) (explode(',', $jawaban->jawaban_pg_opsi_ids)[0]) : null,
                    'opsi_jawaban_ids' => $jawaban->jawaban_pg_opsi_ids ? array_map('intval', explode(',', $jawaban->jawaban_pg_opsi_ids)) : null,
                    'teks_jawaban' => $jawaban->jawaban_essay_text ?? null,
                ] : null,
            ];
        })->values();

        return response()->json([
            'message' => 'Ujian berhasil dimulai',
            'peserta_ujian' => [
                'peserta_ujian_id' => $peserta->peserta_ujian_id,
                'status_ujian' => $peserta->status_ujian,
                'waktu_mulai' => $peserta->waktu_mulai,
                'durasi_menit' => optional($peserta->ujians)->durasi_menit,
                'ujian' => [
                    'ujian_id' => optional($peserta->ujians)->ujian_id,
                    'nama_ujian' => optional($peserta->ujians)->nama_ujian,
                    'mata_pelajaran' => optional($peserta->ujians)->mata_pelajaran,
                    'is_acak_soal' => optional($peserta->ujians)->is_acak_soal,
                ],
                'soal_list' => $soalList,
                'total_soal' => $soalList->count(),
            ],
        ]);
    }

    public function submitJawaban(Request $request)
    {
        $user = $request->user();
        $siswa = Siswa::where('userId', $user->id)->first();
        if (! $siswa) {
            return response()->json(['error' => 'Siswa tidak ditemukan'], 404);
        }

        $pesertaId = (int) $request->input('peserta_ujian_id');
        $soalId = (int) $request->input('soal_id');
        $opsiJawabanId = $request->input('opsi_jawaban_id');
        $opsiJawabanIds = $request->input('opsi_jawaban_ids');
        $teksJawaban = $request->input('teks_jawaban');

        $peserta = PesertaUjian::where('peserta_ujian_id', $pesertaId)->where('siswa_id', $siswa->siswa_id)->first();
        if (! $peserta) return response()->json(['error' => 'Anda tidak memiliki akses ke ujian ini'], 403);
        if ($peserta->status_ujian !== 'SEDANG_DIKERJAKAN') return response()->json(['error' => 'Ujian tidak dalam status sedang dikerjakan', 'status' => $peserta->status_ujian], 400);

        $soal = \App\Models\Soal::with('opsiJawabans')->where('soal_id', $soalId)->first();
        if (! $soal) return response()->json(['error' => 'Soal tidak ditemukan'], 404);

        $existing = Jawaban::where('peserta_ujian_id', $pesertaId)->where('soal_id', $soalId)->first();

        $jawabanPgOpsiIds = null;
        $jawabanEssayText = null;

        if (in_array($soal->tipe_soal, ['PILIHAN_GANDA_SINGLE', 'PILIHAN_GANDA'])) {
            if ($opsiJawabanId) {
                $jawabanPgOpsiIds = (string) $opsiJawabanId;
            }
        } elseif ($soal->tipe_soal === 'PILIHAN_GANDA_MULTIPLE') {
            if (is_array($opsiJawabanIds) && count($opsiJawabanIds) > 0) {
                $jawabanPgOpsiIds = implode(',', $opsiJawabanIds);
            }
        } elseif ($soal->tipe_soal === 'ESSAY') {
            $jawabanEssayText = $teksJawaban ?? null;
        }

        $isEmptyAnswer = ! $jawabanPgOpsiIds && ! $jawabanEssayText;

        if ($existing) {
            if ($isEmptyAnswer) {
                $existing->delete();
                return response()->json(['message' => 'Jawaban berhasil dihapus', 'deleted' => true, 'soal_id' => $soalId]);
            }

            $existing->jawaban_pg_opsi_ids = $jawabanPgOpsiIds;
            $existing->jawaban_essay_text = $jawabanEssayText;
            $existing->save();

            return response()->json(['message' => 'Jawaban berhasil disimpan', 'jawaban' => [
                'jawaban_id' => $existing->jawaban_id,
                'soal_id' => $existing->soal_id,
                'jawaban_pg_opsi_ids' => $existing->jawaban_pg_opsi_ids,
                'jawaban_essay_text' => $existing->jawaban_essay_text,
            ]]);
        }

        if ($isEmptyAnswer) {
            return response()->json(['message' => 'Tidak ada jawaban untuk disimpan', 'empty' => true, 'soal_id' => $soalId]);
        }

        $new = Jawaban::create([
            'peserta_ujian_id' => $pesertaId,
            'soal_id' => $soalId,
            'jawaban_pg_opsi_ids' => $jawabanPgOpsiIds,
            'jawaban_essay_text' => $jawabanEssayText,
        ]);

        return response()->json(['message' => 'Jawaban berhasil disimpan', 'jawaban' => [
            'jawaban_id' => $new->jawaban_id,
            'soal_id' => $new->soal_id,
            'jawaban_pg_opsi_ids' => $new->jawaban_pg_opsi_ids,
            'jawaban_essay_text' => $new->jawaban_essay_text,
        ]]);
    }

    public function finishUjian(Request $request)
    {
        $user = $request->user();
        $siswa = Siswa::where('userId', $user->id)->first();
        if (! $siswa) {
            return response()->json(['error' => 'Siswa tidak ditemukan'], 404);
        }

        $pesertaId = (int) $request->input('peserta_ujian_id');

        $peserta = PesertaUjian::with(['ujians.soal_ujians', 'jawabans' => function ($q) { $q->with('soals.opsiJawabans'); }])
            ->where('peserta_ujian_id', $pesertaId)
            ->where('siswa_id', $siswa->siswa_id)
            ->first();

        if (! $peserta) return response()->json(['error' => 'Anda tidak memiliki akses ke ujian ini'], 403);
        if ($peserta->status_ujian !== 'SEDANG_DIKERJAKAN') return response()->json(['error' => 'Ujian tidak dalam status sedang dikerjakan', 'status' => $peserta->status_ujian], 400);

        $peserta->status_ujian = 'SELESAI';
        $peserta->waktu_selesai = Carbon::now();
        $peserta->save();

        $totalNilai = 0;
        $totalBobot = 0;
        $hasEssay = false;

        $soalUjians = $peserta->ujians->soal_ujians ?? [];

        foreach ($soalUjians as $soalUjian) {
            $totalBobot += $soalUjian->bobot_nilai;
            $jawaban = collect($peserta->jawabans ?? [])->firstWhere('soal_id', $soalUjian->soal_id);

            if ($jawaban && $jawaban->soals) {
                $soal = $jawaban->soals;

                if ($soal->tipe_soal === 'ESSAY') {
                    $hasEssay = true;
                    continue;
                }

                if (in_array($soal->tipe_soal, ['PILIHAN_GANDA_SINGLE', 'PILIHAN_GANDA'])) {
                    $opsiBenar = collect($soal->opsiJawabans)->firstWhere('is_benar', 1);
                    if ($opsiBenar && $jawaban->jawaban_pg_opsi_ids) {
                        $jawabanOpsi = (int) $jawaban->jawaban_pg_opsi_ids;
                        if ($jawabanOpsi === $opsiBenar->opsi_jawaban_id) {
                            $totalNilai += $soalUjian->bobot_nilai;
                        }
                    }
                } elseif ($soal->tipe_soal === 'PILIHAN_GANDA_MULTIPLE') {
                    $opsiBenarIds = collect($soal->opsiJawabans)->filter(function ($o) { return (int)$o->is_benar === 1; })->pluck('opsi_jawaban_id')->sort()->values()->toArray();
                    if ($jawaban->jawaban_pg_opsi_ids) {
                        $jawabanIds = array_map('intval', array_map('trim', explode(',', $jawaban->jawaban_pg_opsi_ids)));
                        sort($jawabanIds);
                        if ($opsiBenarIds === $jawabanIds) {
                            $totalNilai += $soalUjian->bobot_nilai;
                        }
                    }
                }
            }
        }

        $nilaiAkhir = $totalBobot > 0 ? ($totalNilai / $totalBobot) * 100 : 0;

        $hasil = HasilUjian::create([
            'peserta_ujian_id' => $peserta->peserta_ujian_id,
            'nilai_akhir' => $nilaiAkhir,
            'tanggal_submit' => Carbon::now(),
        ]);

        if (! $hasEssay) {
            $peserta->status_ujian = 'DINILAI';
            $peserta->save();
        }

        return response()->json([
            'message' => 'Ujian berhasil diselesaikan',
            'hasil' => [
                'hasil_ujian_id' => $hasil->hasil_ujian_id,
                'nilai_akhir' => $nilaiAkhir,
                'status' => $hasEssay ? 'Menunggu penilaian essay oleh guru' : 'Selesai dinilai',
                'total_soal' => count($soalUjians),
                'soal_terjawab' => count($peserta->jawabans ?? []),
            ],
        ]);
    }
}
