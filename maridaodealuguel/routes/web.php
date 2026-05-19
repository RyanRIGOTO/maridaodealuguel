<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
// UserController not present in the project; comment out to avoid error

// Route::get('/', function () {
//     return view('welcome');
// });

//Route::resource('clientes-prestadores', '...');

Route::get('/novo', function () {
    return view('novo');
})->name('novo');

Route::get("users/{id}", [ClienteController::class, 'show'])->name('users.show');

Route::get('/users/show', function () {
    return view('users.show');
});

Route::resource('clientes', ClienteController::class);

// Route::resource('users', UserController::class);