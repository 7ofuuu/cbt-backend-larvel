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
}
