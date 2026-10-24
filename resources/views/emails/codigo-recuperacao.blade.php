<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Código de recuperação de senha</title>
</head>
<body style="margin:0;padding:24px;background:#f4f2ee;font-family:Arial,Helvetica,sans-serif;color:#2b2a28;">
    <div style="max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #e2ddd5;border-radius:12px;padding:28px;">
        <h1 style="font-size:20px;margin:0 0 16px;">Maridão de Aluguel</h1>

        <p style="margin:0 0 12px;">Olá, {{ $nome }}!</p>
        <p style="margin:0 0 20px;">Recebemos um pedido para redefinir a senha da sua conta. Use o código abaixo:</p>

        <p style="font-size:32px;font-weight:bold;letter-spacing:8px;text-align:center;margin:0 0 20px;padding:14px;background:#f4f2ee;border-radius:8px;">
            {{ $codigo }}
        </p>

        <p style="margin:0 0 12px;">Este código vale por {{ $validadeMinutos }} minutos e só pode ser usado uma vez.</p>
        <p style="margin:0;color:#6b6760;font-size:13px;">Se você não pediu a redefinição, ignore este e-mail. Sua senha continua a mesma.</p>
    </div>
</body>
</html>
