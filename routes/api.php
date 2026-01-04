<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\HasilUjianController;
use App\Http\Controllers\SoalController;

Route::get('/users', [UsersController::class, 'getAllUsers']);
Route::get('/users/admins', [UsersController::class, 'getAllAdmins']);
Route::get('/users/gurus', [UsersController::class, 'getAllGurus']);
Route::get('/users/siswas', [UsersController::class, 'getAllSiswas']);
Route::get('/users/count', [UsersController::class, 'countUsersByRole']);
Route::post('/users', [UsersController::class, 'createUser']);
Route::post('/users/batch', [UsersController::class, 'batchCreateUsers']);
Route::get('/users/{id}', [UsersController::class, 'getUserDetail']);
Route::put('/users/{id}', [UsersController::class, 'updateUser']);
Route::put('/users/{id}/role', [UsersController::class, 'updateUserRole']);
Route::patch('/users/{id}/status', [UsersController::class, 'toggleUserStatus']);
Route::delete('/users/{id}', [UsersController::class, 'deleteUser']);
Route::get('/completed-ujians', [HasilUjianController::class, 'getCompletedUjians']);

Route::prefix('soal')->group(function () {
    Route::post('/', [SoalController::class, 'createSoal']);                          //Create soal
    Route::get('/', [SoalController::class, 'getSoals']);                             //Get all soal +filter
    Route::get('/bank', [SoalController::class, 'getBankSoal']);                      //Get bank soal (grouped)
    Route::get('/bank/{mataPelajaran}/{tingkat}/{jurusan}', [SoalController::class, 'getSoalByBank']); //Get soal specific
    Route::get('/ujian/{ujian_id}/tersedia', [SoalController::class, 'getSoalTersediaUntukUjian']); //Get soal untuk ujian
    Route::post('/assign-bank', [SoalController::class, 'assignBankSoalToUjian']);     //Assign bank soal ke ujian
    Route::get('/{id}', [SoalController::class, 'getSoalById']);                       //Get soal by ID
    Route::put('/{id}', [SoalController::class, 'updateSoal']);                        //Update soal
    Route::delete('/{id}', [SoalController::class, 'deleteSoal']);                     //Delete soal
});