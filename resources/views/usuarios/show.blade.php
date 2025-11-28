<div>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$usuario->nome}}</title>
</head>
<body>
    
    <div> Nome: {{$usuario->nome}} </div>
    <div> Email: {{$usuario->email}} </div>

    <a href="{{ route('usuario.index') }}">
    <button>Voltar</button>
    </a>
</body>
</html>
</div>
