<?php

namespace App\Http\Controllers;

use App\Models\Soal;
use App\Models\OpsiJawaban;
use App\Models\Ujian;
use App\Models\SoalUjian;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoalController extends Controller
{
    // Create Soal with opsi jawaban
    public function createSoal(Request $request)
    {
        try {
            $data = $request->only(['tipe_soal','teks_soal','mata_pelajaran','tingkat','jurusan','soal_gambar','soal_pembahasan','opsi_jawaban']);

            $user = $request->user();
            $guru = null;
            if ($user) {
                $guru = Guru::where('userId', $user->id)->first();
            } elseif ($request->filled('guru_id')) {
                $guru = Guru::where('guru_id', (int)$request->input('guru_id'))->first();
            }

            if (!$guru) {
                return response()->json(['error' => 'Guru tidak ditemukan. Sertakan `guru_id` jika tidak login.'], 400);
            }

            $result = DB::transaction(function () use ($data, $guru) {
                $soal = Soal::create([
                    'tipe_soal' => $data['tipe_soal'] ?? null,
                    'teks_soal' => $data['teks_soal'] ?? null,
                    'mata_pelajaran' => $data['mata_pelajaran'] ?? null,
                    'tingkat' => $data['tingkat'] ?? null,
                    'jurusan' => $data['jurusan'] ?? null,
                    'soal_gambar' => $data['soal_gambar'] ?? null,
                    'soal_pembahasan' => $data['soal_pembahasan'] ?? null,
                    'guru_id' => $guru->guru_id,
                ]);

                if (($soal->tipe_soal ?? '') !== 'ESSAY' && !empty($data['opsi_jawaban']) && is_array($data['opsi_jawaban'])) {
                    foreach ($data['opsi_jawaban'] as $opsi) {
                        OpsiJawaban::create([
                            'soal_id' => $soal->soal_id,
                            'label' => $opsi['label'] ?? null,
                            'teks_opsi' => $opsi['teks_opsi'] ?? null,
                            'is_benar' => $opsi['is_benar'] ?? false,
                        ]);
                    }
                }

                return $soal;
            });

            return response()->json(['message' => 'Soal berhasil dibuat', 'soal_id' => $result->soal_id], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get all soals with filters
    public function getSoals(Request $request)
    {
        try {
            $user = $request->user();

            $query = Soal::with('opsiJawabans');

            if ($request->filled('guru_id')) {
                $query->where('guru_id', (int)$request->query('guru_id'));
            } elseif ($user) {
                $guru = Guru::where('userId', $user->id)->first();
                if ($guru) $query->where('guru_id', $guru->guru_id);
            }

            if ($request->filled('mata_pelajaran')) $query->where('mata_pelajaran', $request->query('mata_pelajaran'));
            if ($request->filled('tingkat')) $query->where('tingkat', $request->query('tingkat'));
            if ($request->filled('jurusan')) $query->where('jurusan', $request->query('jurusan'));
            if ($request->filled('tipe_soal')) $query->where('tipe_soal', $request->query('tipe_soal'));

            $soals = $query->orderBy('createdAt', 'desc')->get();

            return response()->json(['soals' => $soals]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get single soal by ID
    public function getSoalById($id)
    {
        try {
            $soal = Soal::with('opsiJawabans')->where('soal_id', (int)$id)->first();
            if (!$soal) return response()->json(['error' => 'Soal tidak ditemukan'], 404);

            return response()->json(['soal' => $soal]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Update soal
    public function updateSoal(Request $request, $id)
    {
        try {
            $user = $request->user();
            $guruId = null;

            if ($user) {
                $guru = Guru::where('userId', $user->id)->first();
                if ($guru) $guruId = $guru->guru_id;
            } elseif ($request->filled('guru_id')) {
                $guruId = (int)$request->input('guru_id');
            }

            $query = Soal::where('soal_id', (int)$id);
            if (!is_null($guruId)) $query->where('guru_id', $guruId);

            $soal = $query->first();
            if (!$soal) return response()->json(['error' => 'Soal tidak ditemukan'], 404);

            $data = $request->only(['teks_soal','mata_pelajaran','tingkat','jurusan','soal_gambar','soal_pembahasan','opsi_jawaban']);

            $result = DB::transaction(function () use ($soal, $data, $id) {
                $soal->update([
                    'teks_soal' => $data['teks_soal'] ?? $soal->teks_soal,
                    'mata_pelajaran' => $data['mata_pelajaran'] ?? $soal->mata_pelajaran,
                    'tingkat' => $data['tingkat'] ?? $soal->tingkat,
                    'jurusan' => array_key_exists('jurusan', $data) ? $data['jurusan'] : $soal->jurusan,
                    'soal_gambar' => array_key_exists('soal_gambar', $data) ? $data['soal_gambar'] : $soal->soal_gambar,
                    'soal_pembahasan' => array_key_exists('soal_pembahasan', $data) ? $data['soal_pembahasan'] : $soal->soal_pembahasan,
                ]);

                if (!empty($data['opsi_jawaban']) && is_array($data['opsi_jawaban'])) {
                    OpsiJawaban::where('soal_id', (int)$id)->delete();
                    foreach ($data['opsi_jawaban'] as $opsi) {
                        OpsiJawaban::create([
                            'soal_id' => (int)$id,
                            'label' => $opsi['label'] ?? null,
                            'teks_opsi' => $opsi['teks_opsi'] ?? null,
                            'is_benar' => $opsi['is_benar'] ?? false,
                        ]);
                    }
                }

                return $soal;
            });

            return response()->json(['message' => 'Soal berhasil diupdate', 'soal' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Delete soal
    public function deleteSoal(Request $request, $id)
    {
        try {
            $user = $request->user();
            $guruId = null;

            if ($user) {
                $guru = Guru::where('userId', $user->id)->first();
                if ($guru) $guruId = $guru->guru_id;
            } elseif ($request->filled('guru_id')) {
                $guruId = (int)$request->input('guru_id');
            }

            $query = Soal::where('soal_id', (int)$id);
            if (!is_null($guruId)) $query->where('guru_id', $guruId);

            $soal = $query->first();
            if (!$soal) return response()->json(['error' => 'Soal tidak ditemukan'], 404);

            $soal->delete();

            return response()->json(['message' => 'Soal berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get bank soal grouped
    public function getBankSoal(Request $request)
    {
        try {
            $user = $request->user();

            $soalsQuery = Soal::query();
            if ($request->filled('guru_id')) {
                $soalsQuery->where('guru_id', (int)$request->query('guru_id'));
            } elseif ($user) {
                $guru = Guru::where('userId', $user->id)->first();
                if ($guru) $soalsQuery->where('guru_id', $guru->guru_id);
            }

            $soals = $soalsQuery->get(['soal_id','mata_pelajaran','tingkat','jurusan','tipe_soal']);

            $bankSoalMap = [];

            foreach ($soals as $soal) {
                $key = $soal->mata_pelajaran . '|' . $soal->tingkat . '|' . ($soal->jurusan ?? 'umum');
                if (!isset($bankSoalMap[$key])) {
                    $bankSoalMap[$key] = [
                        'mata_pelajaran' => $soal->mata_pelajaran,
                        'tingkat' => $soal->tingkat,
                        'jurusan' => $soal->jurusan ?? null,
                        'soal_ids' => [],
                        'jumlah_soal' => 0,
                        'jumlah_pg' => 0,
                        'jumlah_essay' => 0,
                    ];
                }

                $bankSoalMap[$key]['soal_ids'][] = $soal->soal_id;
                $bankSoalMap[$key]['jumlah_soal']++;

                if ($soal->tipe_soal === 'ESSAY') {
                    $bankSoalMap[$key]['jumlah_essay']++;
                } else {
                    $bankSoalMap[$key]['jumlah_pg']++;
                }
            }

            $bankSoal = array_values($bankSoalMap);

            return response()->json([
                'bankSoal' => $bankSoal,
                'total_grup' => count($bankSoal),
                'total_soal' => $soals->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get soal by specific bank
    public function getSoalByBank(Request $request, $mataPelajaran, $tingkat, $jurusan)
    {
        try {
            $guruId = null;
            if ($request->filled('guru_id')) {
                $guruId = (int)$request->query('guru_id');
            } elseif ($request->user()) {
                $guru = Guru::where('userId', $request->user()->id)->first();
                if ($guru) $guruId = $guru->guru_id;
            }

            $filters = [
                ['mata_pelajaran', '=', $mataPelajaran],
                ['tingkat', '=', $tingkat],
            ];

            if (!is_null($guruId)) {
                $filters[] = ['guru_id', '=', $guruId];
            }

            if ($jurusan && strtolower($jurusan) !== 'umum') {
                $filters[] = ['jurusan', '=', $jurusan];
            } else {
                $filters[] = ['jurusan', '=', null];
            }

            $soals = Soal::with('opsiJawabans')->where($filters)->orderBy('createdAt', 'desc')->get();

            $stats = [
                'total_soal' => $soals->count(),
                'total_pg_single' => $soals->where('tipe_soal', 'PILIHAN_GANDA_SINGLE')->count(),
                'total_pg_multiple' => $soals->where('tipe_soal', 'PILIHAN_GANDA_MULTIPLE')->count(),
                'total_essay' => $soals->where('tipe_soal', 'ESSAY')->count(),
            ];

            return response()->json([
                'bankInfo' => [
                    'mata_pelajaran' => $mataPelajaran,
                    'tingkat' => $tingkat,
                    'jurusan' => $jurusan === 'umum' ? null : $jurusan,
                ],
                'soals' => $soals,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get soal tersedia untuk ujian
    public function getSoalTersediaUntukUjian(Request $request, $ujian_id)
    {
        try {
            $guruId = null;
            if ($request->filled('guru_id')) {
                $guruId = (int)$request->query('guru_id');
            } elseif ($request->user()) {
                $guru = Guru::where('userId', $request->user()->id)->first();
                if ($guru) $guruId = $guru->guru_id;
            }

            $ujianQuery = Ujian::with('soalUjians')->where('ujian_id', (int)$ujian_id);
            if (!is_null($guruId)) $ujianQuery->where('guru_id', $guruId);

            $ujian = $ujianQuery->first();
            if (!$ujian) return response()->json(['error' => 'Ujian tidak ditemukan'], 404);

            $filters = [
                ['mata_pelajaran', '=', $ujian->mata_pelajaran],
                ['tingkat', '=', $ujian->tingkat],
            ];

            if (!is_null($guruId)) {
                $filters[] = ['guru_id', '=', $guruId];
            } elseif (!is_null($ujian->guru_id)) {
                $filters[] = ['guru_id', '=', $ujian->guru_id];
            }

            if ($ujian->jurusan) $filters[] = ['jurusan', '=', $ujian->jurusan];

            $soals = Soal::where($filters)->get(['soal_id','tipe_soal']);

            $soalIdsYangSudahDipakai = $ujian->soalUjians->pluck('soal_id')->toArray();

            $soalIdsTersedia = $soals->filter(function ($s) use ($soalIdsYangSudahDipakai) {
                return !in_array($s->soal_id, $soalIdsYangSudahDipakai);
            })->pluck('soal_id')->toArray();

            $jumlahPG = $soals->filter(function ($s) use ($soalIdsYangSudahDipakai) {
                return $s->tipe_soal !== 'ESSAY' && !in_array($s->soal_id, $soalIdsYangSudahDipakai);
            })->count();

            $jumlahEssay = $soals->filter(function ($s) use ($soalIdsYangSudahDipakai) {
                return $s->tipe_soal === 'ESSAY' && !in_array($s->soal_id, $soalIdsYangSudahDipakai);
            })->count();

            return response()->json([
                'ujian' => [
                    'ujian_id' => $ujian->ujian_id,
                    'nama_ujian' => $ujian->nama_ujian,
                    'mata_pelajaran' => $ujian->mata_pelajaran,
                    'tingkat' => $ujian->tingkat,
                    'jurusan' => $ujian->jurusan,
                ],
                'bank_soal' => [
                    'soal_ids' => $soalIdsTersedia,
                    'jumlah_tersedia' => count($soalIdsTersedia),
                    'jumlah_pg' => $jumlahPG,
                    'jumlah_essay' => $jumlahEssay,
                    'sudah_dipakai' => count($soalIdsYangSudahDipakai),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Assign all soal from bank to ujian
    public function assignBankSoalToUjian(Request $request)
    {
        try {
            $payload = $request->only(['ujian_id','mata_pelajaran','tingkat','jurusan']);

            $guruId = null;
            if ($request->filled('guru_id')) {
                $guruId = (int)$request->input('guru_id');
            } elseif ($request->user()) {
                $guru = Guru::where('userId', $request->user()->id)->first();
                if ($guru) $guruId = $guru->guru_id;
            }

            $ujianQuery = Ujian::with(['soalUjians' => function($q){ $q->orderBy('urutan','desc')->take(1); }])->where('ujian_id', $payload['ujian_id']);
            if (!is_null($guruId)) $ujianQuery->where('guru_id', $guruId);

            $ujian = $ujianQuery->first();
            if (!$ujian) return response()->json(['error' => 'Ujian tidak ditemukan'], 404);

            $filters = [
                ['mata_pelajaran', '=', $payload['mata_pelajaran']],
                ['tingkat', '=', $payload['tingkat']],
            ];
            if (!empty($payload['jurusan'])) $filters[] = ['jurusan', '=', $payload['jurusan']];

            if (!is_null($guruId)) {
                $filters[] = ['guru_id', '=', $guruId];
            }

            $soals = Soal::where($filters)->get(['soal_id']);
            if ($soals->isEmpty()) return response()->json(['error' => 'Tidak ada soal di bank tersebut'], 404);

            $currentUrutan = 0;
            if ($ujian->soalUjians && $ujian->soalUjians->count() > 0) {
                $currentUrutan = $ujian->soalUjians->first()->urutan;
            }

            $insertData = [];
            foreach ($soals as $s) {
                $currentUrutan++;
                $insertData[] = [
                    'ujian_id' => $ujian->ujian_id,
                    'soal_id' => $s->soal_id,
                    'bobot_nilai' => 10,
                    'urutan' => $currentUrutan,
                ];
            }

            // Insert ignoring duplicates
            DB::table('soal_ujians')->insertOrIgnore($insertData);

            // Count how many were actually inserted: not easily retrieved, return requested count
            return response()->json([
                'message' => count($insertData) . ' soal berhasil ditambahkan ke ujian',
                'bank_soal' => [
                    'mata_pelajaran' => $payload['mata_pelajaran'],
                    'tingkat' => $payload['tingkat'],
                    'jurusan' => $payload['jurusan'] ?? 'umum',
                ],
                'jumlah_soal_ditambahkan' => count($insertData),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
