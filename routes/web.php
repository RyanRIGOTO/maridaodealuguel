<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('ola-mundo/', function(){
    $usuario = Usuario::all();
    return view('Usuario.index')->with('Usuario',$usuario);
})->name('index.Usuario');

Route::get('show/{id}/', function(Request $request){
    $usuario = usuario::find($request->id);
    return view('Usuario.show')->with('Usuario',$usuario);
});

Route::get('create', function(){
    return view('usuario.create');
})->name('nova.usuario');

Route::post('store', [UsuarioController::class, 'store'])->name('cria.usuario');

Route::get('edit/{id}', function(Request $request, $id){
    $usuario = usuario::find($request->id);
    return view('usuario.edit')->with('usuario',$usuario);
})->name('editar.usuario');



Route::post('update/{id}', function(Request $request, $id){
    [UsuarioController::class, 'update', 'id'];
})->name('usuario.update');

Route::delete('delete/{id}', [UsuarioController::class, 'destroy', 'id'])->name('excluir.usuario');

Route::get('/teste', function () {
    $usuario = new \App\Models\Usuario();
    $usuario->nome = 'Teste';
    $usuario->email = 'teste@example.com';
    $usuario->save();
    return 'Rota de teste funcionando!';
});
Route::get('usuarios', function () {
   $usuarios = Usuario::all();
   return view('usuario.index', ['usuarios' => $usuarios]);
});

Route::get('show/{id}', function (Resquest $request, $id) {
   $usuario = usuario::find($request -> $id);
   return view('usuario.show', ['usuario' => $usuario]);
});
// Rotas resource para usuários
Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
Route::get('/usuarios/create', [UsuarioController::class, 'create'])->name('usuarios.create');
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])->name('usuarios.show');
Route::get('/usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])->name('usuarios.edit');
Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');


