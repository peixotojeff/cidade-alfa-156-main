# ADR-0008 — Acesso à secretaria em três modos, 404 por padrão

- **Status:** Aceita (provisória — ver ADR-0017)
- **Data:** 2026-11-23 (modo aberto em 2026-11-25)
- **Fase:** Protótipo

## Contexto

A tela `/secretaria` altera status de chamados. O protótipo não tem cadastro de
servidores, mas a banca precisa testar o painel na versão publicada.

## Decisão

`App\Support\AcessoSecretaria` libera o painel em um destes modos, nesta ordem:

1. **Demo local:** `CIDADE_DEMO=true` e requisição de loopback.
2. **Demo publicada:** `SECRETARIA_ABERTA=true`, sem login.
3. **Protegida:** HTTP Basic Auth com `SECRETARIA_USUARIO`/`SECRETARIA_SENHA` (comparação com `hash_equals`).

Sem nenhum deles, a rota responde 404. As rotas têm `throttle:60,1`.

## Consequências

- Na versão publicada qualquer pessoa com o link altera status: só dados fictícios.
- `CIDADE_DEMO=true` nunca deve ir para o Vercel (o `Dockerfile` fixa `false`).
- Não há trilha de quem alterou o quê. Substituição prevista no ADR-0017.

## Evidência

`app/app/Support/AcessoSecretaria.php`, `app/config/cidade.php`, commit `2ede334`.
