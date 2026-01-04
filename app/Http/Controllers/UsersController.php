<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
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
            
            $users = User::with(['admin', 'guru', 'siswa'])
                ->paginate($perPage);

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

                return $userData;
            });

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
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
                        if ($user->admin) {
                            $user->guru->update($profileData);
                        }
                        break;
                    case 'siswa':
                        if ($user->siswa) {
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
