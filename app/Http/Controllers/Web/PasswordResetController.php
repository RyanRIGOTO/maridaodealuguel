<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * "Esqueci minha senha": e-mail -> código OTP -> nova senha.
 * O e-mail e o código validado ficam na sessão entre uma tela e outra.
 */
class PasswordResetController extends Controller
{
    private const MENSAGEM_ENVIO = 'Se existir uma conta com esse e-mail, enviamos um código de 6 dígitos para ele.';

    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
        ]);

        $email = strtolower(trim($data['email']));

        try {
            PasswordResetService::enviarCodigo($email);
        } catch (Throwable) {
            // Não registramos a exceção: a mensagem do SMTP pode conter credenciais.
            Log::error('Falha ao enviar o e-mail de recuperação de senha.');

            return back()->withInput()
                ->with('error', 'Não foi possível enviar o e-mail agora. Tente novamente em instantes.');
        }

        $request->session()->forget('recuperacao.reset_id');
        $request->session()->put('recuperacao.email', $email);

        return redirect()->route('password.code')->with('success', self::MENSAGEM_ENVIO);
    }

    public function showCode(Request $request)
    {
        if (!$request->session()->has('recuperacao.email')) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-code', ['email' => $request->session()->get('recuperacao.email')]);
    }

    public function verifyCode(Request $request)
    {
        $email = $request->session()->get('recuperacao.email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Informe o código recebido por e-mail.',
            'code.digits' => 'O código deve ter 6 números.',
        ]);

        $reset = PasswordResetService::validarCodigo($email, $data['code']);

        $request->session()->put('recuperacao.reset_id', $reset->id);

        return redirect()->route('password.reset');
    }

    public function showReset(Request $request)
    {
        if (!$request->session()->has('recuperacao.reset_id')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request)
    {
        $reset = PasswordResetCode::find($request->session()->get('recuperacao.reset_id'));

        if (!$reset) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            PasswordResetService::redefinirSenha($reset, $data['password']);
        } catch (ValidationException $e) {
            // Código expirou ou foi invalidado entre a validação e a troca de senha.
            $request->session()->forget('recuperacao.reset_id');

            return redirect()->route('password.request')->with('error', $e->getMessage());
        }

        $request->session()->forget('recuperacao');

        return redirect()->route('login')
            ->with('success', 'Senha redefinida com sucesso! Entre com a nova senha.');
    }
}
