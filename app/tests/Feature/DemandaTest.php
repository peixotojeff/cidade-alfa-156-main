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
