-- Cidade Alfa 156+ — esquema para o Postgres do Supabase.
-- Equivale à migração 2026_11_23_000000_create_demandas e a registra na
-- tabela `migrations`, para que um `php artisan migrate` futuro não a repita.
-- Cole no SQL Editor do Supabase e execute uma única vez.

begin;

create table if not exists migrations (
    id serial primary key,
    migration varchar(255) not null,
    batch integer not null
);

create table demandas (
    id bigserial primary key,
    protocolo varchar(21) not null,
    servico varchar(255) not null,
    descricao text not null,
    latitude decimal(9, 6) not null,
    longitude decimal(9, 6) not null,
    status varchar(255) not null,
    prazo timestamp(0) without time zone not null,
    created_at timestamp(0) without time zone null,
    updated_at timestamp(0) without time zone null,
    constraint demandas_protocolo_unique unique (protocolo)
);

create table historico_status (
    id bigserial primary key,
    demanda_id bigint not null,
    status varchar(255) not null,
    notificacao_simulada boolean not null default false,
    ocorrido_em timestamp(0) without time zone not null,
    constraint historico_status_demanda_id_foreign
        foreign key (demanda_id) references demandas (id) on delete cascade
);

insert into migrations (migration, batch)
values ('2026_11_23_000000_create_demandas', 1);

-- O Supabase expõe o schema `public` pela Data API (chave anon). Com RLS ligado
-- e nenhuma policy, essa API não lê nem grava nada; o Laravel conecta como
-- `postgres` (dono das tabelas) e não é afetado.
alter table migrations enable row level security;
alter table demandas enable row level security;
alter table historico_status enable row level security;

commit;
