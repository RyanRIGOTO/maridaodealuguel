<?php

namespace App\Http\Controllers;


use App\Models\ClientePrestador;
use Illuminate\Http\Request;

class ClientePrestadorController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $clientesPrestadores = \App\Models\ClientePrestador::all();
    return response()->json($clientesPrestadores);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ClientePrestador $clientePrestador)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClientePrestador $clientePrestador)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClientePrestador $clientePrestador)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClientePrestador $clientePrestador)
    {
        //
    }
}
