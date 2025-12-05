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
        Usuario::create([
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
        $usuarios = Usuario::find($id);

        return view('usuarios/show')->with('usuarios', $usuarios);
    }

    public function update(Request $request, $id)
    {
        $usuarios = Usuario::findOrFail($id);

        $usuarios->nome = $request->nome;
        $usuarios->cpf = $request->cpf;
        $usuarios->telefone = $request->telefone;
        $usuarios->endereco = $request->endereco;
        $usuarios->email = $request->email;

         return redirect()->route('index.usuario');
    }

    public function destroy($id)
    {
        $usuarios = Usuario::findOrFail($id);

        $usuarios->delete();
        return redirect()->route('index.usuario');
    }

    public function delete($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();
        return redirect()->route('index.usuario');
    }
    
    public function editar($id)
    {
        $usuario = Usuario::findOrFail($id);
        return view('usuario.update')
        ->with(['usuario' => $usuario]);
    }

}
