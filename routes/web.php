<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Autenticado (qualquer usuário)
Route::get('/', function () {
    return view('welcome');
})->middleware('auth')->name('home');

Route::post('logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Públicas
Route::get('login', [AuthController::class, 'create'])->name('login');
Route::post('login', [AuthController::class, 'store'])->name('login.store');

Route::get('register', [UserController::class, 'register'])->name('register');
Route::post('store', [UserController::class, 'store'])->name('users.store');

// Administrativas: tudo aqui dentro exige perfil admin
Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('user/{id}', [UserController::class, 'show'])->name('admin.user.show');
    Route::get('user/{id}/edit', [UserController::class, 'edit'])->name('admin.user.edit');
    Route::put('user/{id}', [UserController::class, 'update'])->name('admin.user.update');
    Route::put('user/{user}/profile', [UserController::class, 'updateProfile'])->name('admin.user.updateProfile');
    Route::put('user/{user}/languages', [UserController::class, 'updateLanguages'])->name('admin.user.updateLanguages');
    Route::delete('user/{id}', [UserController::class, 'destroy'])->name('admin.user.destroy');
});
