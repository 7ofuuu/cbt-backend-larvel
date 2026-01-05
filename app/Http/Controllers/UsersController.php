<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Guru;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    /**
     * Create a new user (Admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUser(Request $request)
    {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $role = $request->input('role');
            $nama = $request->input('nama');
            $kelas = $request->input('kelas');
            $tingkat = $request->input('tingkat');
            $jurusan = $request->input('jurusan');

            // Validate required fields
            if (!$username || !$password || !$role || !$nama) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak lengkap. Username, password, role, dan nama wajib diisi.',
                    'error' => 'Data tidak lengkap. Username, password, role, dan nama wajib diisi.',
                ], 400);
            }

            // Validate role
            if (!in_array($role, ['admin', 'guru', 'siswa'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak valid. Pilih admin, guru, atau siswa.',
                    'error' => 'Role tidak valid. Pilih admin, guru, atau siswa.',
                ], 400);
            }

            // Validate role-specific fields for siswa
            if ($role === 'siswa' && (!$kelas || !$tingkat || !$jurusan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data siswa tidak lengkap. Kelas, tingkat, dan jurusan wajib diisi untuk siswa.',
                    'error' => 'Data siswa tidak lengkap. Kelas, tingkat, dan jurusan wajib diisi untuk siswa.',
                ], 400);
            }

            // Validate tingkat value
            $validTingkats = ['X', 'XI', 'XII'];
            if ($role === 'siswa' && !in_array($tingkat, $validTingkats)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tingkat tidak valid. Pilih salah satu: ' . implode(', ', $validTingkats),
                    'error' => 'Tingkat tidak valid. Pilih salah satu: ' . implode(', ', $validTingkats),
                ], 400);
            }

            // Validate jurusan value
            $validJurusans = ['IPA', 'IPS', 'Bahasa'];
            if ($role === 'siswa' && !in_array($jurusan, $validJurusans)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jurusan tidak valid. Pilih salah satu: ' . implode(', ', $validJurusans),
                    'error' => 'Jurusan tidak valid. Pilih salah satu: ' . implode(', ', $validJurusans),
                ], 400);
            }

            // Validate kelas format for siswa (must be "X-IPA-1" or "XII-IPS-2" format)
            if ($role === 'siswa') {
                // Format: tingkat-jurusan-nomor (contoh: XII-IPA-1, X-IPS-2)
                $kelasPattern = '/^(X|XI|XII)-(IPA|IPS|Bahasa)-(\d+)$/';
                if (!preg_match($kelasPattern, $kelas)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format kelas tidak valid. Gunakan format: tingkat-jurusan-nomor (contoh: XII-IPA-1, X-IPS-2)',
                        'error' => 'Format kelas tidak valid. Gunakan format: tingkat-jurusan-nomor (contoh: XII-IPA-1, X-IPS-2)',
                    ], 400);
                }

                // Validate consistency: kelas must match tingkat and jurusan
                preg_match($kelasPattern, $kelas, $matches);
                $kelasTingkat = $matches[1];
                $kelasJurusan = $matches[2];
                
                if ($kelasTingkat !== $tingkat) {
                    return response()->json([
                        'success' => false,
                        'message' => "Tingkat pada kelas ({$kelasTingkat}) tidak sesuai dengan tingkat yang dipilih ({$tingkat})",
                        'error' => "Tingkat pada kelas ({$kelasTingkat}) tidak sesuai dengan tingkat yang dipilih ({$tingkat})",
                    ], 400);
                }

                if ($kelasJurusan !== $jurusan) {
                    return response()->json([
                        'success' => false,
                        'message' => "Jurusan pada kelas ({$kelasJurusan}) tidak sesuai dengan jurusan yang dipilih ({$jurusan})",
                        'error' => "Jurusan pada kelas ({$kelasJurusan}) tidak sesuai dengan jurusan yang dipilih ({$jurusan})",
                    ], 400);
                }
            }

            // Check if username already exists
            $existingUser = User::where('username', $username)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username sudah digunakan',
                    'error' => 'Username sudah digunakan',
                ], 400);
            }

            // Create user with transaction
            $result = DB::transaction(function () use ($username, $password, $role, $nama, $kelas, $tingkat, $jurusan) {
                $newUser = User::create([
                    'username' => $username,
                    'password' => Hash::make($password),
                    'role' => $role,
                ]);

                // Create role-specific profile
                if ($role === 'siswa') {
                    Siswa::create([
                        'userId' => $newUser->id,
                        'nama_lengkap' => $nama,
                        'kelas' => $kelas,
                        'tingkat' => $tingkat,
                        'jurusan' => $jurusan,
                    ]);
                } else if ($role === 'guru') {
                    Guru::create([
                        'userId' => $newUser->id,
                        'nama_lengkap' => $nama,
                    ]);
                } else if ($role === 'admin') {
                    Admin::create([
                        'userId' => $newUser->id,
                        'nama_lengkap' => $nama,
                    ]);
                }

                return $newUser;
            });

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dibuat',
                'userId' => $result->id,
                'data' => [
                    'userId' => $result->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch create users (Admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchCreateUsers(Request $request)
    {
        try {
            $users = $request->input('users');

            if (!is_array($users) || count($users) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data users harus berupa array dan tidak boleh kosong',
                ], 400);
            }

            $results = [
                'success' => 0,
                'failed' => 0,
                'total' => count($users),
                'errors' => [],
            ];

            foreach ($users as $userData) {
                try {
                    $username = $userData['username'] ?? null;
                    $password = $userData['password'] ?? null;
                    $role = $userData['role'] ?? null;
                    $nama = $userData['nama'] ?? null;
                    $kelas = $userData['kelas'] ?? null;
                    $tingkat = $userData['tingkat'] ?? null;
                    $jurusan = $userData['jurusan'] ?? null;

                    // Validate required fields
                    if (!$username || !$password || !$role || !$nama) {
                        $results['failed']++;
                        $results['errors'][] = ['username' => $username, 'error' => 'Data tidak lengkap'];
                        continue;
                    }

                    // Validate role
                    if (!in_array($role, ['admin', 'guru', 'siswa'])) {
                        $results['failed']++;
                        $results['errors'][] = ['username' => $username, 'error' => 'Role tidak valid'];
                        continue;
                    }

                    // Validate role-specific fields for siswa
                    if ($role === 'siswa' && (!$kelas || !$tingkat || !$jurusan)) {
                        $results['failed']++;
                        $results['errors'][] = ['username' => $username, 'error' => 'Data siswa tidak lengkap (kelas, tingkat, jurusan)'];
                        continue;
                    }

                    // Validate tingkat value
                    $validTingkats = ['X', 'XI', 'XII'];
                    if ($role === 'siswa' && !in_array($tingkat, $validTingkats)) {
                        $results['failed']++;
                        $results['errors'][] = ['username' => $username, 'error' => "Tingkat tidak valid: \"{$tingkat}\". Pilih: " . implode(', ', $validTingkats)];
                        continue;
                    }

                    // Validate jurusan value
                    $validJurusans = ['IPA', 'IPS', 'Bahasa'];
                    if ($role === 'siswa' && !in_array($jurusan, $validJurusans)) {
                        $results['failed']++;
                        $results['errors'][] = ['username' => $username, 'error' => "Jurusan tidak valid: \"{$jurusan}\". Pilih: " . implode(', ', $validJurusans)];
                        continue;
                    }

                    // Validate kelas format for siswa (tingkat-jurusan-nomor)
                    if ($role === 'siswa') {
                        $kelasPattern = '/^(X|XI|XII)-(IPA|IPS|Bahasa)-(\d+)$/';
                        if (!preg_match($kelasPattern, $kelas)) {
                            $results['failed']++;
                            $results['errors'][] = [
                                'username' => $username,
                                'error' => "Format kelas tidak valid: \"{$kelas}\". Gunakan format: tingkat-jurusan-nomor (contoh: XII-IPA-1)"
                            ];
                            continue;
                        }

                        // Validate consistency: kelas must match tingkat and jurusan
                        preg_match($kelasPattern, $kelas, $matches);
                        $kelasTingkat = $matches[1];
                        $kelasJurusan = $matches[2];
                        
                        if ($kelasTingkat !== $tingkat) {
                            $results['failed']++;
                            $results['errors'][] = [
                                'username' => $username,
                                'error' => "Tingkat pada kelas ({$kelasTingkat}) tidak sesuai dengan tingkat ({$tingkat})"
                            ];
                            continue;
                        }

                        if ($kelasJurusan !== $jurusan) {
                            $results['failed']++;
                            $results['errors'][] = [
                                'username' => $username,
                                'error' => "Jurusan pada kelas ({$kelasJurusan}) tidak sesuai dengan jurusan ({$jurusan})"
                            ];
                            continue;
                        }
                    }

                    // Check if username already exists
                    $existingUser = User::where('username', $username)->first();
                    if ($existingUser) {
                        $results['failed']++;
                        $results['errors'][] = ['username' => $username, 'error' => 'Username sudah digunakan'];
                        continue;
                    }

                    // Create user with transaction
                    DB::transaction(function () use ($username, $password, $role, $nama, $kelas, $tingkat, $jurusan) {
                        $newUser = User::create([
                            'username' => $username,
                            'password' => Hash::make($password),
                            'role' => $role,
                        ]);

                        // Create role-specific profile
                        if ($role === 'siswa') {
                            Siswa::create([
                                'userId' => $newUser->id,
                                'nama_lengkap' => $nama,
                                'kelas' => $kelas,
                                'tingkat' => $tingkat,
                                'jurusan' => $jurusan,
                            ]);
                        } else if ($role === 'guru') {
                            Guru::create([
                                'userId' => $newUser->id,
                                'nama_lengkap' => $nama,
                            ]);
                        } else if ($role === 'admin') {
                            Admin::create([
                                'userId' => $newUser->id,
                                'nama_lengkap' => $nama,
                            ]);
                        }
                    });

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'username' => $userData['username'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Return response with results at root level for frontend compatibility
            return response()->json([
                'success' => $results['success'],
                'failed' => $results['failed'],
                'total' => $results['total'],
                'errors' => $results['errors'],
                'message' => 'Batch import selesai',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal batch import users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user role (Admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserRole(Request $request, $id)
    {
        try {
            $role = $request->input('role');

            // Validate role
            if (!in_array($role, ['admin', 'guru', 'siswa'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak valid',
                ], 400);
            }

            $user = User::with(['admin', 'guru', 'siswa'])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            // If role is the same, skip
            if ($user->role === $role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role sudah sama',
                ], 400);
            }

            DB::transaction(function () use ($user, $role) {
                // Delete old profile
                if ($user->admin) {
                    $user->admin->delete();
                }
                if ($user->guru) {
                    $user->guru->delete();
                }
                if ($user->siswa) {
                    $user->siswa->delete();
                }

                // Update role
                $user->role = $role;
                $user->save();

                // Create new profile with default data
                if ($role === 'admin') {
                    Admin::create([
                        'userId' => $user->id,
                        'nama_lengkap' => 'Admin',
                    ]);
                } else if ($role === 'guru') {
                    Guru::create([
                        'userId' => $user->id,
                        'nama_lengkap' => 'Guru',
                    ]);
                } else if ($role === 'siswa') {
                    Siswa::create([
                        'userId' => $user->id,
                        'nama_lengkap' => 'Siswa',
                        'kelas' => '-',
                        'tingkat' => '-',
                        'jurusan' => '-',
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Role user berhasil diubah',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah role user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle user status (Admin only).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleUserStatus($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            $user->status_aktif = !$user->status_aktif;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $user->status_aktif ? 'User diaktifkan' : 'User dinonaktifkan',
                'data' => [
                    'status_aktif' => $user->status_aktif,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get all users with their related profiles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllUsers(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $role = $request->input('role');
            $status_aktif = $request->input('status_aktif');
            $username = $request->input('username');
            
            $query = User::with(['admin', 'guru', 'siswa']);
            
            // Filter by role
            if ($role) {
                $query->where('role', $role);
            }
            
            // Filter by status_aktif
            if ($status_aktif !== null) {
                $query->where('status_aktif', $status_aktif === 'true' || $status_aktif === '1');
            }
            
            // Filter by username (exact match for checking availability)
            if ($username) {
                $query->where('username', $username);
            }
            
            // Order by createdAt desc (same as Express)
            $query->orderBy('createdAt', 'desc');
            
            $users = $query->paginate($perPage);

            $data = $users->map(function ($user) {
                $userData = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'status_aktif' => $user->status_aktif,
                    'createdAt' => $user->createdAt,
                    'updatedAt' => $user->updatedAt,
                ];

                // Add role-specific profile data
                switch ($user->role) {
                    case 'admin':
                        $userData['admin'] = $user->admin ? [
                            'admin_id' => $user->admin->admin_id,
                            'nama_lengkap' => $user->admin->nama_lengkap,
                        ] : null;
                        $userData['profile'] = $userData['admin'];
                        break;
                    case 'guru':
                        $userData['guru'] = $user->guru ? [
                            'guru_id' => $user->guru->guru_id,
                            'nama_lengkap' => $user->guru->nama_lengkap,
                        ] : null;
                        $userData['profile'] = $userData['guru'];
                        break;
                    case 'siswa':
                        $userData['siswa'] = $user->siswa ? [
                            'siswa_id' => $user->siswa->siswa_id,
                            'nama_lengkap' => $user->siswa->nama_lengkap,
                            'kelas' => $user->siswa->kelas,
                            'tingkat' => $user->siswa->tingkat,
                            'jurusan' => $user->siswa->jurusan,
                        ] : null;
                        $userData['profile'] = $userData['siswa'];
                        break;
                }

                return $userData;
            });

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'users' => $data,  // Add 'users' key for Express compatibility
                'data' => $data,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

     /**
     * Get all users with admin role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllAdmins(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            
            $admins = User::with('admin')
                ->where('role', 'admin')
                ->paginate($perPage);

            $data = $admins->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'status_aktif' => $user->status_aktif,
                    'createdAt' => $user->createdAt,
                    'updatedAt' => $user->updatedAt,
                    'profile' => $user->admin ? [
                        'admin_id' => $user->admin->admin_id,
                        'nama_lengkap' => $user->admin->nama_lengkap,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Admin users retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $admins->currentPage(),
                    'last_page' => $admins->lastPage(),
                    'per_page' => $admins->perPage(),
                    'total' => $admins->total(),
                    'from' => $admins->firstItem(),
                    'to' => $admins->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve admin users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all users with guru role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllGurus(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            
            $gurus = User::with('guru')
                ->where('role', 'guru')
                ->paginate($perPage);

            $data = $gurus->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'status_aktif' => $user->status_aktif,
                    'createdAt' => $user->createdAt,
                    'updatedAt' => $user->updatedAt,
                    'profile' => $user->guru ? [
                        'guru_id' => $user->guru->guru_id,
                        'nama_lengkap' => $user->guru->nama_lengkap,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Guru users retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $gurus->currentPage(),
                    'last_page' => $gurus->lastPage(),
                    'per_page' => $gurus->perPage(),
                    'total' => $gurus->total(),
                    'from' => $gurus->firstItem(),
                    'to' => $gurus->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve guru users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all users with siswa role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSiswas(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $tingkat = $request->input('tingkat');
            $jurusan = $request->input('jurusan');
            $kelas = $request->input('kelas');
            
            $query = User::with('siswa')
                ->where('role', 'siswa');
            
            // Add search functionality if search parameter is provided
            if ($search) {
                $query->whereHas('siswa', function ($q) use ($search) {
                    $q->where('nama_lengkap', 'like', '%' . $search . '%');
                });
            }
            
            // Add filter for tingkat
            if ($tingkat) {
                $query->whereHas('siswa', function ($q) use ($tingkat) {
                    $q->where('tingkat', $tingkat);
                });
            }
            
            // Add filter for jurusan
            if ($jurusan) {
                $query->whereHas('siswa', function ($q) use ($jurusan) {
                    $q->where('jurusan', $jurusan);
                });
            }
            
            // Add filter for kelas
            if ($kelas) {
                $query->whereHas('siswa', function ($q) use ($kelas) {
                    $q->where('kelas', 'like', '%' . $kelas);
                });
            }
            
            $siswas = $query->paginate($perPage);

            $data = $siswas->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'status_aktif' => $user->status_aktif,
                    'createdAt' => $user->createdAt,
                    'updatedAt' => $user->updatedAt,
                    'profile' => $user->siswa ? [
                        'siswa_id' => $user->siswa->siswa_id,
                        'nama_lengkap' => $user->siswa->nama_lengkap,
                        'kelas' => $user->siswa->kelas,
                        'tingkat' => $user->siswa->tingkat,
                        'jurusan' => $user->siswa->jurusan,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Siswa users retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $siswas->currentPage(),
                    'last_page' => $siswas->lastPage(),
                    'per_page' => $siswas->perPage(),
                    'total' => $siswas->total(),
                    'from' => $siswas->firstItem(),
                    'to' => $siswas->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve siswa users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Count users by role.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countUsersByRole()
    {
        try {
            $adminCount = User::where('role', 'admin')->count();
            $guruCount = User::where('role', 'guru')->count();
            $siswaCount = User::where('role', 'siswa')->count();
            $totalCount = User::count();

            return response()->json([
                'success' => true,
                'message' => 'User count by role retrieved successfully',
                'data' => [
                    'total' => $totalCount,
                    'admin' => $adminCount,
                    'guru' => $guruCount,
                    'siswa' => $siswaCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to count users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user detail by ID with role-specific profile.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserDetail($id)
    {
        try {
            $user = User::with(['admin', 'guru', 'siswa'])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'status_aktif' => $user->status_aktif,
                'createdAt' => $user->createdAt,
                'updatedAt' => $user->updatedAt,
            ];

            // Add role-specific profile data
            switch ($user->role) {
                case 'admin':
                    $userData['profile'] = $user->admin ? [
                        'admin_id' => $user->admin->admin_id,
                        'nama_lengkap' => $user->admin->nama_lengkap,
                    ] : null;
                    break;
                case 'guru':
                    $userData['profile'] = $user->guru ? [
                        'guru_id' => $user->guru->guru_id,
                        'nama_lengkap' => $user->guru->nama_lengkap,
                    ] : null;
                    break;
                case 'siswa':
                    $userData['profile'] = $user->siswa ? [
                        'siswa_id' => $user->siswa->siswa_id,
                        'nama_lengkap' => $user->siswa->nama_lengkap,
                        'kelas' => $user->siswa->kelas,
                        'tingkat' => $user->siswa->tingkat,
                        'jurusan' => $user->siswa->jurusan,
                    ] : null;
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'User detail retrieved successfully',
                'data' => $userData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user detail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user by ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::with(['admin', 'guru', 'siswa'])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Update user basic info
            if ($request->has('username')) {
                $user->username = $request->username;
            }
            if ($request->has('password')) {
                $user->password = bcrypt($request->password);
            }
            if ($request->has('status_aktif')) {
                $user->status_aktif = $request->status_aktif;
            }

            $user->save();

            // Update role-specific profile
            if ($request->has('profile')) {
                $profileData = $request->profile;

                switch ($user->role) {
                    case 'admin':
                        if ($user->admin) {
                            $user->admin->update($profileData);
                        }
                        break;
                    case 'guru':
                        if ($user->guru) {
                            $user->guru->update($profileData);
                        }
                        break;
                    case 'siswa':
                        if ($user->siswa) {
                            // Validate kelas format if kelas is being updated
                            if (isset($profileData['kelas'])) {
                                $kelas = $profileData['kelas'];
                                $tingkat = $profileData['tingkat'] ?? $user->siswa->tingkat;
                                $jurusan = $profileData['jurusan'] ?? $user->siswa->jurusan;
                                
                                // Validate tingkat value
                                $validTingkats = ['X', 'XI', 'XII'];
                                if (isset($profileData['tingkat']) && !in_array($tingkat, $validTingkats)) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'Tingkat tidak valid. Pilih salah satu: ' . implode(', ', $validTingkats),
                                    ], 400);
                                }
                                
                                // Validate jurusan value
                                $validJurusans = ['IPA', 'IPS', 'Bahasa'];
                                if (isset($profileData['jurusan']) && !in_array($jurusan, $validJurusans)) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'Jurusan tidak valid. Pilih salah satu: ' . implode(', ', $validJurusans),
                                    ], 400);
                                }
                                
                                // Validate kelas format
                                $kelasPattern = '/^(X|XI|XII)-(IPA|IPS|Bahasa)-(\d+)$/';
                                if (!preg_match($kelasPattern, $kelas)) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'Format kelas tidak valid. Gunakan format: tingkat-jurusan-nomor (contoh: XII-IPA-1)',
                                    ], 400);
                                }
                                
                                // Validate consistency
                                preg_match($kelasPattern, $kelas, $matches);
                                $kelasTingkat = $matches[1];
                                $kelasJurusan = $matches[2];
                                
                                if ($kelasTingkat !== $tingkat) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => "Tingkat pada kelas ({$kelasTingkat}) tidak sesuai dengan tingkat ({$tingkat})",
                                    ], 400);
                                }
                                
                                if ($kelasJurusan !== $jurusan) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => "Jurusan pada kelas ({$kelasJurusan}) tidak sesuai dengan jurusan ({$jurusan})",
                                    ], 400);
                                }
                            }
                            
                            $user->siswa->update($profileData);
                        }
                        break;
                }
            }

            // Reload user with relationships
            $user->load(['admin', 'guru', 'siswa']);

            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'status_aktif' => $user->status_aktif,
                'createdAt' => $user->createdAt,
                'updatedAt' => $user->updatedAt,
            ];

            // Add role-specific profile data
            switch ($user->role) {
                case 'admin':
                    $userData['profile'] = $user->admin ? [
                        'admin_id' => $user->admin->admin_id,
                        'nama_lengkap' => $user->admin->nama_lengkap,
                    ] : null;
                    break;
                case 'guru':
                    $userData['profile'] = $user->guru ? [
                        'guru_id' => $user->guru->guru_id,
                        'nama_lengkap' => $user->guru->nama_lengkap,
                    ] : null;
                    break;
                case 'siswa':
                    $userData['profile'] = $user->siswa ? [
                        'siswa_id' => $user->siswa->siswa_id,
                        'nama_lengkap' => $user->siswa->nama_lengkap,
                        'kelas' => $user->siswa->kelas,
                        'tingkat' => $user->siswa->tingkat,
                        'jurusan' => $user->siswa->jurusan,
                    ] : null;
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $userData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user by ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUser($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $username = $user->username;
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'data' => [
                    'id' => $id,
                    'username' => $username,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
