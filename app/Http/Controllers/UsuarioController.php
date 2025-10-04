<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        // Lista todos os usuários
    }

    public function create()
    {
        // Mostra formulário de criação
    }

    public function store(Request $request)
    {
        // Salva novo usuário
    }

    public function show($id)
    {
        // Mostra um usuário específico
    }

    public function edit($id)
    {
        // Mostra formulário de edição
    }

    public function update(Request $request, $id)
    {
        // Atualiza usuário
    }

    public function destroy($id)
    {
        // Remove usuário
    }
}
