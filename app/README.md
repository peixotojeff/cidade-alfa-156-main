# Cidade Alfa 156+ — app Laravel

Protótipo do Cidade Alfa 156+ (Laravel 12, Blade). Como executar, testar e
publicar: [README da raiz](../README.md).

## Mapa rápido

| Caminho | O que tem |
| --- | --- |
| `routes/web.php` | Uma rota por objetivo: início, abrir, acompanhar, indicadores, análise, secretaria |
| `app/Http/Controllers/DemandaController.php` | Todo o fluxo do cidadão e da secretaria |
| `app/Support/` | Acesso à secretaria, indicadores, chamado de exemplo, importação do SP156 |
| `config/cidade.php` | Catálogo de serviços e prazos (SLA), datas de corte do SP156, acesso à secretaria |
| `database/migrations/` | Esquema (`demandas`, `historico_status`) |
| `database/supabase/schema.sql` | Mesmo esquema para o Postgres do Supabase, com RLS |
| `database/seeders/data/` | Amostra do SP156 e seu [README](database/seeders/data/README.md) |
| `public/cidade.css` | Tokens e estilos do design system ([DESIGN.md](../DESIGN.md)) |
| `tests/Feature/DemandaTest.php` | Testes do fluxo completo |

As decisões por trás dessas escolhas estão em [`docs/adr/`](../docs/adr/README.md).
