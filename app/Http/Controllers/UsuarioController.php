<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    public function index()
    {
        // Lista todos os usuários
        $usuarios = Usuario::all();
        return view('usuarios/index')->with('usuarios', $usuarios);
    }

    public function create()
    {
        // Mostra formulário de criação
    }

    public function store(Request $request)
    {
        // Salva novo usuário
        Usuarios::create([
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'telefone' =>$request->telefone,
            'endereco' => $request->endereco,
            'email' => $request->email,
            'senha' => bcrypt($request->senha)
         ]);
            return redirect()->route('index.usuario');
    }

    public function show($id)
    {
        $usuarios = Usuarios::find($id);

        return view('usuarios/show')->with('usuarios', $usuarios);
    }

    public function edit($id)
    {
        // Mostra formulário de edição
    }

    public function update(Request $request, $id)
    {
        Usuarios::create([
            'nome' => $request->nome,
            'telefone' =>$request->telefone,
            'endereco' => $request->endereco
         ]);

         return redirect()->route('index.usuario');
    }

    public function destroy($id)
    {
        $usuarios = Usuarios::findOrFail($id);

        $usuarios->delete();
        return redirect()->route('index.usuario');
    }
}
