<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;

Route::get('/novo', function () {
    return view('novo');
})->name('novo');

Route::get('/users/show', function () {
    return view('users.show');
});

Route::get('users/{id}', [ClienteController::class, 'show'])->name('users.show');

Route::resource('clientes', ClienteController::class);
