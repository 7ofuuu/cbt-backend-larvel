<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Get all users with their related profiles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllUsers()
    {
        try {
            $users = User::with(['admin', 'guru', 'siswa'])
                ->get()
                ->map(function ($user) {
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
                'data' => $users,
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllAdmins()
    {
        try {
            $admins = User::with('admin')
                ->where('role', 'admin')
                ->get()
                ->map(function ($user) {
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
                'data' => $admins,
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllGurus()
    {
        try {
            $gurus = User::with('guru')
                ->where('role', 'guru')
                ->get()
                ->map(function ($user) {
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
                'data' => $gurus,
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSiswas()
    {
        try {
            $siswas = User::with('siswa')
                ->where('role', 'siswa')
                ->get()
                ->map(function ($user) {
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
                'data' => $siswas,
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
}
