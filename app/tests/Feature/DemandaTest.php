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
        $this->get('/acompanhar/ALFA-0000000000000000')->assertNotFound()->assertSee('Não encontramos esta página');
    }

    public function test_cada_objetivo_tem_sua_pagina_e_o_menu_marca_a_atual(): void
    {
        $this->get('/')->assertOk()->assertSee('Abrir um chamado')->assertSee('Iluminação pública')->assertSee('aria-current="page"', false);
        $this->get('/chamados/novo')->assertOk()->assertSee('Qual é o problema?')->assertSee('name="servico"', false);
        $this->get('/acompanhar')->assertOk()->assertSee('Como está o seu chamado?');
        $this->post('/consultar', ['protocolo' => 'errado'])->assertRedirect('/acompanhar')->assertSessionHasErrors('protocolo');
        // Formato válido mas inexistente: volta para a consulta com orientação (sem 404).
        $this->post('/consultar', ['protocolo' => 'alfa-0123456789abcdef'])->assertRedirect('/acompanhar')->assertSessionHasErrors('protocolo');
        $protocolo = basename($this->post('/chamados', $this->dados())->headers->get('Location'));
        $this->post('/consultar', ['protocolo' => strtolower($protocolo)])->assertRedirect("/acompanhar/$protocolo");
    }

    public function test_chamados_do_sp156_sao_somente_leitura_e_medidos_na_data_de_corte(): void
    {
        $this->seed(\Database\Seeders\Sp156Seeder::class);
        config(['cidade.demo' => true]);
        $aberto = \App\Models\Demanda::where('origem', 'sp156')->where('status', 'recebida')->orderBy('created_at')->first();
        $resolvido = \App\Models\Demanda::where('origem', 'sp156')->where('status', 'resolvida')->first();

        // Sem avaliação e sem mudança de status.
        $this->get('/acompanhar/'.$resolvido->protocolo)->assertSee('Chamado do SP156')->assertDontSee('Como foi o atendimento?');
        $this->post('/acompanhar/'.$resolvido->protocolo.'/avaliacao', ['avaliacao' => 'excelente'])->assertSessionHasErrors('avaliacao');
        $this->post('/secretaria/'.$aberto->protocolo.'/status', ['status' => 'em_atendimento'])->assertStatus(409);
        $this->assertSame('recebida', $aberto->fresh()->status);

        // Atraso medido na data de corte da planilha, não hoje.
        $corte = \App\Models\Demanda::corteSp156($aberto->created_at);
        $this->assertTrue($aberto->referenciaPrazo()->equalTo($corte));
        $recente = \App\Models\Demanda::forceCreate(['protocolo' => 'ALFA-00000000000000CD', 'servico' => 'arvore', 'descricao' => 'x', 'latitude' => 0, 'longitude' => 0,
            'status' => 'recebida', 'origem' => 'sp156', 'created_at' => '2026-09-28', 'prazo' => '2026-10-28']);
        $this->assertSame('no_prazo', $recente->situacaoPrazo(), 'Prazo após o corte (08/10) não conta como atrasado.');
        $this->get('/secretaria?situacao=atrasados&servico=arvore')->assertDontSee('ALFA-00000000000000CD');
        $this->get('/acompanhar/ALFA-00000000000000CD')->assertSee('em 08/10/2026');

        $g = \App\Support\Indicadores::calcular()['geral'];
        $this->assertLessThan($g['abertas'], $g['atrasadas']);
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

    public function test_recarga_da_amostra_pela_secretaria_exige_acesso(): void
    {
        config(['cidade.demo' => false, 'cidade.secretaria' => ['usuario' => 'gestor', 'senha' => 'segredo-forte']]);
        $remoto = ['REMOTE_ADDR' => '192.0.2.20'];
        $this->withServerVariables($remoto)->post('/secretaria/amostra-sp156')->assertUnauthorized();
        $this->assertSame(0, \App\Models\Demanda::count());

        $this->withServerVariables($remoto + ['PHP_AUTH_USER' => 'gestor', 'PHP_AUTH_PW' => 'segredo-forte'])
            ->post('/secretaria/amostra-sp156')->assertRedirect('/secretaria')->assertSessionHas('aviso');
        $this->assertSame(600, \App\Models\Demanda::where('origem', 'sp156')->count());
    }

    public function test_chamado_de_exemplo_mostra_o_fluxo_completo(): void
    {
        $url = '/acompanhar/'.\App\Support\ChamadoExemplo::PROTOCOLO;
        $this->get('/acompanhar')->assertSee($url, false);

        $this->get($url)->assertOk()
            ->assertSee('Chamado de exemplo')
            ->assertSeeInOrder(['Recebida', '16/11/2026 08:42', 'Em atendimento', '17/11/2026 10:15', 'Aviso ao cidadão (simulado)', 'Resolvida', '20/11/2026 16:30'])
            ->assertSee('Resolvido no prazo · 4 dias')
            ->assertSee('Resolvido 3 dias antes do prazo')
            ->assertDontSee('Data não informada');

        // Não regrava a cada visita; restaura se for apagado.
        $id = \App\Models\Demanda::firstWhere('protocolo', \App\Support\ChamadoExemplo::PROTOCOLO)->id;
        $this->get($url);
        $this->assertSame($id, \App\Models\Demanda::firstWhere('protocolo', \App\Support\ChamadoExemplo::PROTOCOLO)->id);
        \Illuminate\Support\Facades\DB::table('historico_status')->where('demanda_id', $id)->delete();
        $this->get($url)->assertSee('Aviso ao cidadão (simulado)');

        // Fora dos indicadores e da fila.
        $this->assertSame(0, \App\Support\Indicadores::calcular()['geral']['total']);
        config(['cidade.demo' => true]);
        $this->get('/secretaria?situacao=todos')->assertDontSee(\App\Support\ChamadoExemplo::PROTOCOLO);
    }

    public function test_cidadao_avalia_uma_vez_depois_de_resolvido_e_entra_no_npa(): void
    {
        config(['cidade.demo' => true]);
        $local = parse_url($this->post('/chamados', $this->dados())->headers->get('Location'), PHP_URL_PATH);
        $protocolo = basename($local);

        // Antes de resolver: sem formulário e sem gravação.
        $this->get($local)->assertDontSee('Como foi o atendimento?');
        $this->post("$local/avaliacao", ['avaliacao' => 'excelente'])->assertSessionHasErrors('avaliacao');
        $this->assertNull(\App\Models\Demanda::firstWhere('protocolo', $protocolo)->avaliacao);

        $this->post("/secretaria/$protocolo/status", ['status' => 'em_atendimento']);
        $this->post("/secretaria/$protocolo/status", ['status' => 'resolvida']);
        $this->get($local)->assertSee('Como foi o atendimento?')->assertSee('Péssimo');

        $this->post("$local/avaliacao", ['avaliacao' => 'qualquer'])->assertSessionHasErrors('avaliacao');
        $this->post("$local/avaliacao", ['avaliacao' => 'pessimo'])->assertRedirect("$local#avaliacao");
        $this->get($local)->assertSee('Obrigado!')->assertSee('Avaliação do atendimento')->assertDontSee('Como foi o atendimento?');

        // Uma vez só.
        $this->post("$local/avaliacao", ['avaliacao' => 'excelente'])->assertSessionHasErrors('avaliacao');
        $this->assertSame('pessimo', \App\Models\Demanda::firstWhere('protocolo', $protocolo)->avaliacao);

        // NPA: 1 péssimo e 1 excelente -> 0; o exemplo não entra.
        $outro = \App\Models\Demanda::forceCreate(['protocolo' => 'ALFA-00000000000000AB', 'servico' => 'via', 'descricao' => 'x', 'latitude' => 0, 'longitude' => 0,
            'status' => 'resolvida', 'prazo' => now()->addDay(), 'finalizada_em' => now(), 'avaliacao' => 'excelente']);
        $this->get('/acompanhar/'.\App\Support\ChamadoExemplo::PROTOCOLO)->assertSee('Excelente');
        $g = \App\Support\Indicadores::calcular()['geral'];
        $this->assertSame([2, 0, ['excelente' => 1, 'bom' => 0, 'pessimo' => 1]], [$g['avaliadas'], $g['npa'], $g['avaliacoes']]);
        $this->get('/indicadores')->assertSee('Satisfação (NPA)')->assertSee('2 avaliações de cidadãos');
    }

    public function test_secretaria_aberta_para_demonstracao_dispensa_login(): void
    {
        config(['cidade.demo' => false, 'cidade.secretaria' => ['aberta' => true, 'usuario' => 'gestor', 'senha' => 'segredo-forte']]);
        $remoto = ['REMOTE_ADDR' => '192.0.2.20'];
        $this->withServerVariables($remoto)->get('/secretaria')->assertOk()->assertSee('Modo demonstração');
        $this->withServerVariables($remoto)->get('/')->assertSee('/secretaria', false);

        config(['cidade.secretaria.aberta' => false]);
        $this->withServerVariables($remoto)->get('/secretaria')->assertUnauthorized();
    }

    public function test_pagina_de_analise_mostra_o_passo_a_passo_da_eda(): void
    {
        $this->get('/analise')->assertOk()
            ->assertSee('Como analisamos')
            ->assertSeeInOrder(['Coletar', 'Diagnosticar a qualidade', 'Higienizar', 'Criar atributos', 'Explorar', 'Fase 5'])
            ->assertSee('colab.research.google.com/github/', false)
            ->assertSee('fase4/figuras/01_tempo_atendimento_por_servico.png', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_pagina_de_modelos_mostra_as_quatro_tecnicas(): void
    {
        $this->get('/modelos')->assertOk()
            ->assertSeeInOrder(['Regressão', 'Classificação', 'Clusterização', 'Anomalias'])
            ->assertSee('colab.research.google.com/github/', false)
            ->assertSee('portfolio/figuras/03_classificacao.png', false)
            ->assertSee('Gradient Boosting');
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
