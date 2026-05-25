@extends('layout.padrao')
@section('conteudo')
    <h1>Lista de Clientes</h1>
    <ul>
        @foreach($clientes as $cliente)
            <li>{{ $cliente->name }}</li>
        @endforeach
    </ul>
@endsection