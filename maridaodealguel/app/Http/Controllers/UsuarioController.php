<?php

namespace App\Http\Controllers;

abstract class Controller
{
     public function index()
    {
        $Usuario = User::all(); // Corrigido: use o namespace importado
        return response()->json($Usuario);
    }

     public function store(Request $request)
    {
        $Usuario = User::create($request->all()); // Corrigido: use o namespace importado
        return response()->json($Usuario, 201);
    }

     public function show($id)
    {
        $Usuario = User::find($id); // Corrigido: use o namespace importado
        if (!$Usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }
        return response()->json($Usuario);
    }

     public function update(Request $request, $id)
    {
        $Usuario = User::find($id); // Corrigido: use o namespace importado
        if (!$Usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }
        $Usuario->update($request->all());
        return response()->json($Usuario);
    }

     public function destroy($id)
    {
        $Usuario = User::find($id); // Corrigido: use o namespace importado
        if (!$Usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }
        $Usuario->delete();
        return response()->json(null, 204);
    }
}
