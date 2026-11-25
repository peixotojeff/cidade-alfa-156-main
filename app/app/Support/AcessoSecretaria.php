<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Controla quem pode abrir a tela /secretaria.
 *
 * - Demonstração local: CIDADE_DEMO=true e requisição vinda de loopback.
 * - Publicação (ex.: Vercel): HTTP Basic Auth com SECRETARIA_USUARIO e SECRETARIA_SENHA.
 *   Sem senha configurada, a tela não existe (404).
 */
class AcessoSecretaria
{
    public static function demoLocal(Request $request): bool
    {
        return config('cidade.demo') && in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true);
    }

    public static function protegidaPorSenha(): bool
    {
        return filled(config('cidade.secretaria.usuario')) && filled(config('cidade.secretaria.senha'));
    }

    /** Indica se o link da secretaria deve aparecer no menu. */
    public static function visivel(Request $request): bool
    {
        return self::demoLocal($request) || self::protegidaPorSenha();
    }

    public static function exigir(Request $request): void
    {
        if (self::demoLocal($request)) {
            return;
        }

        abort_unless(self::protegidaPorSenha(), 404);

        $usuarioOk = hash_equals((string) config('cidade.secretaria.usuario'), (string) $request->getUser());
        $senhaOk = hash_equals((string) config('cidade.secretaria.senha'), (string) $request->getPassword());

        abort_unless($usuarioOk && $senhaOk, 401, 'Acesso restrito à secretaria.', [
            'WWW-Authenticate' => 'Basic realm="Secretaria Cidade Alfa", charset="UTF-8"',
        ]);
    }
}
