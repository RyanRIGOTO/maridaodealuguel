<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientes = Cliente::all();
        return view('clientes.index', compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clientes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:clientes,email',
            'telefone'        => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'password'        => 'required|string|min:6',
        ]);

        // Cliente::create([
        //     'name'            => $request->name,
        //     'email'           => $request->email,
        //     'telefone'        => $request->telefone,
        //     'data_nascimento' => $request->data_nascimento,
        //     'password'        => bcrypt($request->password),
        // ]);

        Cliente::create([
        
        'email' => 'teste@email.com',
        'password' => bcrypt('123456')
        ]);

        return redirect()->route('users.index')
                         ->with('success', 'Usuário criado com sucesso!');
    }

    /**
     * Display the specified resource.  ← GET /users/{id}
     */
    // public function show(string $id)
    // {
    //     $user = Cliente::findOrFail($id);
    //     return view('users.show', compact('user'));
    // }

    public function show($id)
{
    $user = Cliente::findOrFail($id);

    return view('users.show', compact('user'));
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = Cliente::findOrFail($id);
        return view('clientes.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Cliente::findOrFail($id);
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:clientes,email,' . $id,
            'telefone'        => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
        ]);
        $user->update($request->except(['password', '_token', '_method']));
        return redirect()->route('users.show', $user->id)
                         ->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Cliente::findOrFail($id);
        $user->delete();
        return redirect()->route('users.index')
                         ->with('success', 'Usuário removido com sucesso!');
    }
}