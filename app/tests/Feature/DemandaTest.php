<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaTest extends TestCase
{
    use RefreshDatabase;

    private function dados(): array
    {
        return ['servico' => 'iluminacao', 'descricao' => 'Poste apagado na praça de teste', 'latitude' => '-23.55', 'longitude' => '-46.63'];
    }

    public function test_cidadao_abre_e_acompanha_ate_resolucao(): void
    {
        config(['cidade.demo' => true]);
        $this->travelTo(now()->setDate(2026, 11, 23)->setTime(10, 0));
        $abertura = $this->post('/chamados', $this->dados())->assertRedirect();
        $consulta = parse_url($abertura->headers->get('Location'), PHP_URL_PATH);
        $protocolo = basename($consulta);
        $this->assertMatchesRegularExpression('/^ALFA-[A-F0-9]{16}$/', $protocolo);
        $this->get($consulta)->assertOk()->assertSee('Recebida')->assertSee('26/11/2026');
        $this->get('/secretaria')->assertOk()->assertSee($protocolo);
        $this->post("/secretaria/$protocolo/status", ['status' => 'resolvida'])->assertStatus(409);
        $this->post("/secretaria/$protocolo/status", ['status' => 'em_atendimento'])->assertRedirect('/secretaria');
        $this->post("/secretaria/$protocolo/status", ['status' => 'resolvida'])->assertRedirect('/secretaria');
        $this->get($consulta)->assertSeeInOrder(['Recebida', 'Em atendimento', 'Resolvida'], false);
        $this->get('/secretaria')->assertSee('2 notificações simuladas');
    }

    public function test_rejeita_campos_invalidos_sem_gravar_chamado(): void
    {
        config(['cidade.demo' => true]);
        $this->from('/')->post('/chamados', array_replace($this->dados(), ['latitude' => 91, 'longitude' => '', 'servico' => 'inexistente']))
            ->assertRedirect('/')->assertSessionHasErrors(['latitude', 'longitude', 'servico']);
        $this->get('/secretaria')->assertSee('Nenhum chamado registrado');
    }

    public function test_painel_fechado_por_padrao_e_bloqueado_remotamente(): void
    {
        config(['cidade.demo' => false]);
        $this->get('/secretaria')->assertNotFound();
        config(['cidade.demo' => true]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])->get('/secretaria')->assertNotFound();
        $this->get('/acompanhar/ALFA-0000000000000000')->assertNotFound();
    }

    public function test_cada_objetivo_tem_sua_pagina_e_o_menu_marca_a_atual(): void
    {
        $this->get('/')->assertOk()->assertSee('Abrir um chamado')->assertSee('Iluminação pública')->assertSee('aria-current="page"', false);
        $this->get('/chamados/novo')->assertOk()->assertSee('Qual é o problema?')->assertSee('name="servico"', false);
        $this->get('/acompanhar')->assertOk()->assertSee('Como está o seu chamado?');
        $this->post('/consultar', ['protocolo' => 'errado'])->assertRedirect('/acompanhar')->assertSessionHasErrors('protocolo');
        $this->post('/consultar', ['protocolo' => 'alfa-0123456789abcdef'])->assertRedirect('/acompanhar/ALFA-0123456789ABCDEF');
    }

    public function test_confirma_registro_ao_abrir_chamado(): void
    {
        $destino = $this->post('/chamados', $this->dados())->headers->get('Location');
        $this->get($destino)->assertSee('Recebemos seu chamado.');
        $this->get($destino)->assertDontSee('Recebemos seu chamado.');
    }

    public function test_importa_amostra_do_sp156_de_forma_idempotente(): void
    {
        $this->post('/chamados', $this->dados());
        $this->seed(\Database\Seeders\Sp156Seeder::class);
        $this->seed(\Database\Seeders\Sp156Seeder::class);

        $this->assertSame(600, \App\Models\Demanda::where('origem', 'sp156')->count());
        $this->assertSame(1, \App\Models\Demanda::where('origem', 'cidadao')->count(), 'Chamados do app não podem ser apagados pela importação.');
        $this->assertSame(0, \App\Models\Demanda::where('origem', 'sp156')->whereNotIn('servico', array_keys(config('cidade.servicos')))->count());

        $resolvida = \App\Models\Demanda::where('origem', 'sp156')->where('status', 'resolvida')->first();
        $this->assertNotNull($resolvida->finalizada_em);
        $this->get('/acompanhar/'.$resolvida->protocolo)->assertOk()->assertSee('dados abertos do SP156')->assertSee('Data não informada');
    }

    public function test_situacao_do_prazo_e_indicadores(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(10, 0));
        $base = ['servico' => 'via', 'descricao' => 'x', 'latitude' => 0, 'longitude' => 0];
        $criar = fn (string $protocolo, string $status, string $aberta, ?string $final) => \App\Models\Demanda::forceCreate($base + [
            'protocolo' => $protocolo, 'status' => $status, 'prazo' => \Illuminate\Support\Carbon::parse($aberta)->addDays(7),
            'created_at' => $aberta, 'finalizada_em' => $final,
        ]);
        $criar('ALFA-0000000000000001', 'resolvida', '2026-10-01', '2026-10-05'); // 4 dias, no prazo
        $criar('ALFA-0000000000000002', 'resolvida', '2026-10-01', '2026-10-20'); // 19 dias, fora
        $criar('ALFA-0000000000000003', 'recebida', '2026-10-01', null);         // atrasado
        $criar('ALFA-0000000000000004', 'recebida', '2026-10-30', null);         // no prazo

        $this->assertSame('resolvido_no_prazo', \App\Models\Demanda::firstWhere('protocolo', 'ALFA-0000000000000001')->situacaoPrazo());
        $this->assertSame('resolvido_fora_do_prazo', \App\Models\Demanda::firstWhere('protocolo', 'ALFA-0000000000000002')->situacaoPrazo());
        $this->assertSame('atrasado', \App\Models\Demanda::firstWhere('protocolo', 'ALFA-0000000000000003')->situacaoPrazo());

        $via = \App\Support\Indicadores::calcular()['servicos']['via'];
        $this->assertSame([50, 1, 2], [$via['pct_no_prazo'], $via['atrasadas'], $via['abertas']]);
        $this->get('/indicadores')->assertOk()->assertSee('Resolvidos no prazo')->assertSee('50%');
        $this->get('/acompanhar/ALFA-0000000000000003')->assertSee('Atrasado');

        config(['cidade.demo' => true]);
        $this->get('/secretaria?situacao=atrasados')->assertSee('ALFA-0000000000000003')->assertDontSee('ALFA-0000000000000004');
    }

    public function test_secretaria_publicada_exige_senha(): void
    {
        config(['cidade.demo' => false, 'cidade.secretaria' => ['usuario' => 'gestor', 'senha' => 'segredo-forte']]);
        $remoto = ['REMOTE_ADDR' => '192.0.2.20'];
        $this->withServerVariables($remoto)->get('/secretaria')
            ->assertUnauthorized()->assertHeader('WWW-Authenticate');
        $this->withServerVariables($remoto + ['PHP_AUTH_USER' => 'gestor', 'PHP_AUTH_PW' => 'errada'])
            ->get('/secretaria')->assertUnauthorized();
        $this->withServerVariables($remoto + ['PHP_AUTH_USER' => 'gestor', 'PHP_AUTH_PW' => 'segredo-forte'])
            ->get('/secretaria')->assertOk();
    }
}
