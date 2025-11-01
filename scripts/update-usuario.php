<?php
// Uso: php scripts/update-usuario.php --cpf=11122233344 --nome="Beatriz Silva"
// ou: php scripts/update-usuario.php --id=1 --nome="Beatriz Silva"

$opts = getopt('', ['cpf::', 'id::', 'nome::', 'email::', 'telefone::', 'endereco::', 'senha::']);

if (empty($opts)) {
    echo "Uso:\n  php scripts/update-usuario.php --cpf=11122233344 --nome=\"Beatriz Silva\"\n  ou --id=1 --nome=\"Beatriz Silva\"\n";
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

$query = null;
if (!empty($opts['id'])) {
    $query = Usuario::where('id', $opts['id']);
} elseif (!empty($opts['cpf'])) {
    $query = Usuario::where('cpf', $opts['cpf']);
} elseif (!empty($opts['email'])) {
    $query = Usuario::where('email', $opts['email']);
} else {
    echo "É necessário informar --id ou --cpf ou --email para localizar o usuário.\n";
    exit(1);
}

$usuario = $query->first();
if (! $usuario) {
    echo "Usuário não encontrado.\n";
    exit(1);
}

$data = [];
if (isset($opts['nome'])) $data['nome'] = $opts['nome'];
if (isset($opts['telefone'])) $data['telefone'] = $opts['telefone'];
if (isset($opts['endereco'])) $data['endereco'] = $opts['endereco'];
if (isset($opts['email'])) $data['email'] = $opts['email'];
if (isset($opts['senha'])) $data['senha'] = Hash::make($opts['senha']);

if (empty($data)) {
    echo "Nada para atualizar. Passe --nome, --telefone, --endereco, --email ou --senha.\n";
    exit(1);
}

$usuario->update($data);
echo "Usuário atualizado com sucesso. ID={$usuario->id}\n";
echo json_encode($usuario->fresh()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
