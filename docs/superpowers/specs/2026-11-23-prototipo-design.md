# Cidade Alfa 156+ — protótipo funcional

Laravel 12, Blade e SQLite, com dados fictícios e execução local. O cidadão escolhe serviço, informa descrição e confirma pin (por toque, GPS mediante permissão ou coordenadas digitadas). Foto não entra nesta versão. O SLA usa dias corridos do catálogo e fica registrado no chamado. Protocolo aleatório serve para consulta.

A secretaria de demonstração, habilitada só para execução local, avança Recebida → Em atendimento → Resolvida. O histórico e o registro de notificação simulada são persistidos juntos. Não envia WhatsApp. Sem CPFs, anexos, Oracle, Databricks ou publicação.

Validação: abertura com coordenadas válidas, prazo conhecido, consulta pelo protocolo, transições autorizadas, histórico visível e rejeição de coordenadas inválidas e saltos de status.
