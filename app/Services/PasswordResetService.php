<?php

namespace App\Services;

use App\Mail\CodigoRecuperacaoSenha;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Recuperação de senha com código OTP de 6 dígitos enviado por e-mail.
 */
class PasswordResetService
{
    public const VALIDADE_MINUTOS = 10;
    public const MAX_TENTATIVAS = 5;

    /**
     * Gera e envia um código novo. Não faz nada se a conta não existir ou estiver
     * inativa, para não revelar quais e-mails estão cadastrados.
     */
    public static function enviarCodigo(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user || !$user->isActive()) {
            return;
        }

        $codigo = (string) random_int(100000, 999999);

        DB::transaction(function () use ($user, $codigo) {
            // Um código novo invalida os anteriores.
            PasswordResetCode::where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            PasswordResetCode::create([
                'user_id' => $user->id,
                'code' => Hash::make($codigo),
                'expires_at' => now()->addMinutes(self::VALIDADE_MINUTOS),
            ]);
        });

        Mail::mailer(self::mailer())
            ->to($user->email)
            ->send(new CodigoRecuperacaoSenha($user->name, $codigo, self::VALIDADE_MINUTOS));
    }

    /**
     * Confere o código digitado. Cada erro conta uma tentativa.
     */
    public static function validarCodigo(string $email, string $codigo): PasswordResetCode
    {
        $reset = PasswordResetCode::whereHas('user', fn ($q) => $q->where('email', $email))
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        self::garantirUtilizavel($reset);

        if (!Hash::check($codigo, $reset->code)) {
            $reset->increment('attempts');
            $restantes = self::MAX_TENTATIVAS - $reset->attempts;

            throw ValidationException::withMessages([
                'code' => $restantes > 0
                    ? "Código incorreto. Você ainda tem {$restantes} tentativa(s)."
                    : 'Número de tentativas excedido. Solicite um novo código.',
            ]);
        }

        return $reset;
    }

    /**
     * Grava a nova senha e marca o código como usado (uso único).
     */
    public static function redefinirSenha(PasswordResetCode $reset, string $senha): void
    {
        self::garantirUtilizavel($reset);

        DB::transaction(function () use ($reset, $senha) {
            $reset->user->update(['password' => Hash::make($senha)]);
            $reset->update(['used_at' => now()]);

            // Encerra o "lembrar-me" e os tokens da API de outros dispositivos.
            $reset->user->forceFill(['remember_token' => null])->save();
            $reset->user->tokens()->delete();
        });

        AuditLogService::log('senha_redefinida', 'users', $reset->user_id);
    }

    private static function garantirUtilizavel(?PasswordResetCode $reset): void
    {
        $erro = match (true) {
            $reset === null || $reset->used_at !== null => 'Código inválido. Solicite um novo.',
            $reset->isExpired() => 'Este código expirou. Solicite um novo.',
            $reset->attempts >= self::MAX_TENTATIVAS => 'Número de tentativas excedido. Solicite um novo código.',
            default => null,
        };

        if ($erro) {
            throw ValidationException::withMessages(['code' => $erro]);
        }
    }

    /**
     * Usa o Gmail quando SMTP_MAILER e SMTP_SENHA estão no .env.
     * Sem eles, cai no mailer padrão (MAIL_MAILER=log grava o e-mail em storage/logs).
     */
    private static function mailer(): ?string
    {
        $configurado = filled(config('mail.mailers.gmail.username'))
            && filled(config('mail.mailers.gmail.password'));

        return $configurado ? 'gmail' : null;
    }
}
