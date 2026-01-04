<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

// Routes untuk User Management
Route::get('/users', [UsersController::class, 'getAllUsers']);
Route::get('/users/admins', [UsersController::class, 'getAllAdmins']);
Route::get('/users/gurus', [UsersController::class, 'getAllGurus']);
Route::get('/users/siswas', [UsersController::class, 'getAllSiswas']);
Route::get('/users/count', [UsersController::class, 'countUsersByRole']);
Route::post('/users', [UsersController::class, 'createUser']);           // Create user
Route::post('/users/batch', [UsersController::class, 'batchCreateUsers']); // Batch create users
Route::get('/users/{id}', [UsersController::class, 'getUserDetail']);
Route::put('/users/{id}', [UsersController::class, 'updateUser']);
Route::put('/users/{id}/role', [UsersController::class, 'updateUserRole']); // Update role
Route::patch('/users/{id}/status', [UsersController::class, 'toggleUserStatus']); // Toggle status
Route::delete('/users/{id}', [UsersController::class, 'deleteUser']);
