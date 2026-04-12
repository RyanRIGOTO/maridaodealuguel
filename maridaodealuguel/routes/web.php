<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientePrestadorController;

// Route::get('/', function () {
//     return view('welcome');
// });

//Route::resource('clientes-prestadores', 'App\Http\Controllers\ClientePrestadorController');  

Route::resource('clientes-prestadores', ClientePrestadorController::class);
