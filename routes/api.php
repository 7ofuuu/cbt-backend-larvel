<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

Route::get('/users', [UsersController::class, 'getAllUsers']);
Route::get('/users/admins', [UsersController::class, 'getAllAdmins']);
Route::get('/users/gurus', [UsersController::class, 'getAllGurus']);
Route::get('/users/siswas', [UsersController::class, 'getAllSiswas']);
Route::get('/users/count', [UsersController::class, 'countUsersByRole']);
Route::get('/users/{id}', [UsersController::class, 'getUserDetail']);
Route::put('/users/{id}', [UsersController::class, 'updateUser']);
Route::delete('/users/{id}', [UsersController::class, 'deleteUser']);
