# Cidade Alfa 156+

Protótipo Laravel 12 em `app/`. Instruções de execução e deploy no [README.md](README.md).

## Interface

Toda mudança visual segue o [DESIGN.md](DESIGN.md): o design system Skylearn
adaptado para serviço público. Antes de mexer em views Blade ou em
`app/public/cidade.css`:

- Use os tokens CSS de `:root` em `cidade.css`; não escreva cores, raios ou
  sombras soltos.
- Texto nunca abaixo de 16px; controles principais com 56px de altura.
- Estados de erro e sucesso sempre com ícone + texto, nunca só cor.
- Nada de gamificação (estrelas, confete, mascote) — ver a tabela de adaptações.
- Ícones ficam em `resources/views/cidade/icone.blade.php` (traço 2px, pontas
  arredondadas).
