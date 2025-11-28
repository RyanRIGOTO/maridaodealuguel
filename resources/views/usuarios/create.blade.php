<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Criar Usuario</title>
</head>

<body>
    <form action="{{ route('cria.usuarios') }}" method="POST">
        @csrf

        <div>
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome">

        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email">

        </div>
        <div>
            <label for="endereco">Endereço</label>
            <input type="text" id="endereco" name="endereco">
        </div>
        <div>
            <button type="submit">Criar</button>
        </div>
    </form>
</body>

</html>

