# PBL — Data Science | FIAP

- **Projeto:** Cidade Alfa 156+ — Cidadania Digital na Cidade Alfa
- **Equipe:** Data Drivens
- **Última entrega informada pela equipe:** Fase 3.

## Protótipo funcional — Laravel

Código em `app/`, com Laravel 12, Blade e SQLite. Abra um chamado fictício, informe o local pelo pin do mapa ou pela localização do navegador, e acompanhe o prazo e o histórico pelo protocolo. A secretaria local avança o status e registra notificações simuladas, sem envio real. Anexos, apoio comunitário e integrações com a plataforma de dados ficam fora deste recorte.

### Executar localmente (Linux ou WSL)

No pacote de código-fonte, instale as dependências com `cd app && composer install`. Use PHP 8.4.1 ou posterior com as extensões SQLite (`pdo_sqlite`), `mbstring` e XML e volte à raiz do projeto. Rode `bash rodar-demo.sh` e abra `http://127.0.0.1:8156`. No ambiente em que o protótipo foi construído as dependências estão instaladas e o script usa um PHP isolado. O launcher só migra `app/database/demo.sqlite`; o banco e a chave de sessão são gerados localmente e não fazem parte do pacote.

- `/secretaria` é uma tela de demonstração, sem autenticação: o script a habilita só em loopback. **Não a coloque atrás de proxy ou túnel público**, nem use dados reais.
- O mapa usa Leaflet com imagens do OpenStreetMap; se as imagens falharem, ainda é possível digitar coordenadas fictícias. A geolocalização depende de permissão do navegador e origem segura (`localhost` ou HTTPS); para demonstrações use localização simulada.
- Testes: na pasta `app`, rode `php vendor/bin/phpunit` ou, neste ambiente, `../.tooling/frankenphp-stable php-cli vendor/bin/phpunit` com `APP_KEY` definida com uma chave de teste aleatória.

### Publicar no Vercel (container + Supabase)

O Vercel não roda PHP nativamente. O `Dockerfile` da raiz empacota o Laravel com FrankenPHP, e o Vercel o detecta e usa o preset **Container**. O SQLite não persiste no Vercel, então o banco de produção é um Postgres do Supabase.

1. **Banco (Supabase).** Crie um projeto e, no *SQL Editor*, execute uma vez [`app/database/supabase/schema.sql`](app/database/supabase/schema.sql). Ele cria as tabelas, registra a migração e liga RLS para a Data API do Supabase não expor os chamados.
2. **String de conexão.** Em *Connect → Session pooler*, copie a URI (`postgresql://postgres.<ref>:<senha>@aws-0-<região>.pooler.supabase.com:5432/postgres`) e acrescente `?sslmode=require`. Use o *session pooler* (porta 5432): ele aceita IPv4, e o *transaction pooler* (6543) quebra os prepared statements do PDO.
3. **Projeto no Vercel.** Importe o repositório, com *Root Directory* na raiz (onde está o `Dockerfile`). Em *Settings → Environment Variables*, defina:

   | Variável | Valor |
   | --- | --- |
   | `APP_KEY` | saída de `php artisan key:generate --show`; mantenha fixa |
   | `APP_URL` | URL pública, ex.: `https://cidade-alfa.vercel.app` |
   | `DB_URL` | URI do passo 2 |
   | `SECRETARIA_USUARIO` / `SECRETARIA_SENHA` | credenciais da `/secretaria` (Basic Auth); sem elas, a tela fica em 404 |

   O `Dockerfile` já define `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=pgsql`, sessão em cookie criptografado e `CIDADE_DEMO=false`. **Nunca defina `CIDADE_DEMO=true` no Vercel.**
4. **Deploy.** Faça push ou rode `vercel deploy --prod`. Depois de mudar variáveis, refaça o deploy.

Novas migrações não rodam no boot do container. Rode-as à parte, com `DB_URL` apontando para o Supabase: `php artisan migrate --force` (requer a extensão `pdo_pgsql`).

## Fase 3 — Arquitetura de dados e Databricks

Fonte: `/opt/data/attachments/Data_Drivens_PBL_Fase3_.zip` (quatro PDFs).

| Entrega | O que o material documenta |
| --- | --- |
| 1. Componentes (p. 1) | Equipe, desafio e foco em tornar a resolução das solicitações e seus prazos visíveis ao cidadão. |
| 2. Arquitetura de dados (pp. 1–2) | Proposta de manter dados sensíveis no ambiente local e enviar dados pseudonimizados ao Databricks. A arquitetura prevê camadas Raw, Bronze, Prata e Ouro e consumo pelo Power BI. |
| 3. Implantação Big Data (pp. 1–3) | Databricks Free Edition, catálogo `cidadania_digital_cidade_alfa`, schemas Bronze/Prata/Ouro e cinco CSVs sintéticos no volume `arquivos_raw`. |
| 4. Tabela Delta e SQL (pp. 1–8) | Criação da tabela Bronze `tb_cadastro_cidadao` e demonstração de `SELECT`, `INSERT`, `UPDATE` e `DELETE`, com consultas de conferência. |

**Limite da evidência:** os PDFs documentam a carga Raw e operações em uma tabela Bronze. A criação dos schemas Prata e Ouro não demonstra que eles estejam populados; o painel Power BI aparece no desenho, sem demonstração de funcionamento. Não houve reexecução no Databricks.

## Fase 1 — Da ideação ao modelo conceitual de dados

O objetivo é investigar um problema, delimitar uma solução centrada no usuário e traduzir as regras do negócio em um modelo conceitual de dados. O caso **ReciclaData**, sobre coleta de resíduos na Cidade Alfa, é o exemplo do guia, não a definição do nosso desafio.

Não use os nomes, RMs, evidências ou requisitos do exemplo como dados da equipe.

## Checklist da entrega

| Status | Entregável | Referência |
| --- | --- | --- |
| [ ] | Mapa inicial: integrantes em ordem alfabética, RMs, nome da equipe e desafio | Guia §2; slide 1 |
| [ ] | Dossiê: contexto, causas, público afetado e evidências da pesquisa | Guia §3–4; slide 2 |
| [ ] | Mapa da empatia, persona, jornada e visão do produto com o quadro É / Não é / Faz / Não faz | Guia §4; slides 3–6, reunidos em um PDF |
| [ ] | Regras de negócio e requisitos funcionais e não funcionais | Guia §5; slide 7 |
| [ ] | Modelo conceitual de dados (DER), coerente com as regras e requisitos | Guia §6; slide 8 indica Oracle Data Modeler |
| [ ] | ZIP final com entregáveis nomeados, ordenados e revisados, sem duplicatas | Guia §7 |

**Formato do DER:** o quadro-resumo do guia indica JPG; as seções 6 e 7 orientam PDF. Confirmar com o tutor qual formato deve ir no ZIP. O material consultado não informa prazo de envio.

## Fonte da Fase 1

Arquivo original: `/opt/data/attachments/Cap 1 - PBL - Construindo a Ponte entre Problemas e Ideias A Magia da Ideação.zip`.

Conteúdo conferido: `PBL - Cap 01 - Exercício Guiado - Fase 1 - Entregáveis.docx` e `Template_Exercicio_Guiado.pptx` (8 slides). O ZIP e os dois arquivos internos passaram na verificação de integridade; as orientações do guia e o texto dos slides foram consultados. Os originais permanecem no anexo, sem alterações.
