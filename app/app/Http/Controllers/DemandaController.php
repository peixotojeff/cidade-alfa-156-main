<?php

namespace App\Http\Controllers;

use App\Models\Demanda;
use App\Support\AcessoSecretaria;
use App\Support\ChamadoExemplo;
use App\Support\Indicadores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DemandaController extends Controller
{
    public function inicio()
    {
        return view('cidade.inicio', ['servicos' => config('cidade.servicos')]);
    }

    public function novo()
    {
        return view('cidade.novo', ['servicos' => config('cidade.servicos')]);
    }

    public function consulta()
    {
        return view('cidade.consulta');
    }

    public function abrir(Request $request)
    {
        $servicos = config('cidade.servicos');
        $dados = $request->validate([
            'servico' => ['required', 'string', Rule::in(array_keys($servicos))],
            'descricao' => ['required', 'string', 'max:1200'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'servico.required' => 'Selecione o tipo de serviço.',
            'servico.in' => 'Selecione um serviço do catálogo.',
            'descricao.required' => 'Descreva o problema.',
            'descricao.max' => 'Use até 1.200 caracteres na descrição.',
            'latitude.*' => 'Informe uma latitude válida de -90 a 90.',
            'longitude.*' => 'Informe uma longitude válida de -180 a 180.',
        ]);

        $demanda = DB::transaction(function () use ($dados, $servicos) {
            $demanda = Demanda::create($dados + [
                'protocolo' => 'ALFA-'.strtoupper(bin2hex(random_bytes(8))),
                'status' => 'recebida',
                'prazo' => now()->addDays($servicos[$dados['servico']]['dias']),
            ]);
            $demanda->registrarEvento('recebida');

            return $demanda;
        });

        return redirect()->route('acompanhar', $demanda->protocolo)->with('novo', true);
    }

    public function consultar(Request $request)
    {
        $valor = $request->input('protocolo');
        if (! is_string($valor) || ! preg_match('/^ALFA-[A-F0-9]{16}$/D', strtoupper($valor))) {
            return redirect()->route('consulta')->withErrors(['protocolo' => 'Confira o protocolo: ele tem o formato ALFA- seguido de 16 letras e números.'])->withInput();
        }

        $protocolo = strtoupper($valor);
        if ($protocolo !== ChamadoExemplo::PROTOCOLO && ! Demanda::where('protocolo', $protocolo)->exists()) {
            return redirect()->route('consulta')->withErrors(['protocolo' => 'Não encontramos um chamado com esse protocolo. Confira os 16 caracteres depois de ALFA-.'])->withInput();
        }

        return redirect()->route('acompanhar', $protocolo);
    }

    public function acompanhar(string $protocolo)
    {
        // O exemplo é criado (ou restaurado) na primeira visita, igual no local e em produção.
        $demanda = $protocolo === ChamadoExemplo::PROTOCOLO ? ChamadoExemplo::obter() : $this->buscar($protocolo);

        return response()->view('cidade.acompanhar', [
            'demanda' => $demanda,
            'servico' => config('cidade.servicos')[$demanda->servico],
            'historico' => $demanda->historico()->orderBy('id')->get(),
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    /** Avaliação do cidadão: só depois de resolvido e uma única vez. */
    public function avaliar(Request $request, string $protocolo)
    {
        $dados = $request->validate(
            ['avaliacao' => ['required', 'string', Rule::in(array_keys(Demanda::AVALIACOES))]],
            ['avaliacao.*' => 'Escolha Excelente, Bom ou Péssimo.'],
        );
        $demanda = $this->buscar($protocolo);
        $gravou = Demanda::whereKey($demanda->id)->where('origem', 'cidadao')->where('status', 'resolvida')->whereNull('avaliacao')
            ->update(['avaliacao' => $dados['avaliacao'], 'avaliada_em' => now()]);
        $volta = redirect()->route('acompanhar', $demanda->protocolo)->withFragment('avaliacao');

        return $gravou === 1
            ? $volta->with('avaliado', true)
            : $volta->withErrors(['avaliacao' => 'Este chamado já foi avaliado ou ainda não foi resolvido.']);
    }

    public function secretaria(Request $request)
    {
        $this->somenteDemo($request);

        $servicos = config('cidade.servicos');
        $filtros = $this->filtrosFila($request);

        // Fila de trabalho: por padrão, os abertos com o prazo mais próximo (ou mais estourado) primeiro.
        $consulta = Demanda::query()->reais()
            ->when($filtros['servico'], fn ($q, $s) => $q->where('servico', $s))
            ->when($filtros['situacao'] === 'abertos', fn ($q) => $q->where('status', '!=', 'resolvida'))
            ->when($filtros['situacao'] === 'atrasados', fn ($q) => $q->atrasados())
            ->when($filtros['situacao'] === 'resolvidos', fn ($q) => $q->where('status', 'resolvida'));
        $consulta = $filtros['situacao'] === 'resolvidos' || $filtros['situacao'] === 'todos'
            ? $consulta->latest('created_at')->latest('id')
            : $consulta->orderBy('prazo')->orderBy('id');

        return view('cidade.secretaria', [
            'demandas' => $consulta->paginate(15)->withQueryString(),
            'filtros' => $filtros,
            'indicadores' => Indicadores::calcular(),
            'contagem' => Demanda::query()->reais()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'servicos' => $servicos,
            'simuladas' => DB::table('historico_status')->where('notificacao_simulada', true)->count(),
        ]);
    }

    /** Carrega (ou recarrega) a amostra do SP156. Idempotente: só substitui origem = 'sp156'. */
    public function importarSp156(Request $request)
    {
        $this->somenteDemo($request);
        (new \Database\Seeders\Sp156Seeder)->run();
        $total = Demanda::where('origem', \App\Support\Sp156Amostra::ORIGEM)->count();

        return redirect()->route('secretaria')->with('aviso', "Amostra do SP156 carregada: {$total} chamados reais.");
    }

    /** Fase 4: passo a passo da EDA (números e figuras gerados por fase4/pipeline_fase4.py). */
    public function analise()
    {
        $arquivo = public_path('fase4/eda.json');
        $eda = is_file($arquivo) ? json_decode(file_get_contents($arquivo), true) : null;
        abort_unless(is_array($eda), 404);

        return view('cidade.analise', ['eda' => $eda, 'notebook' => config('cidade.notebook_eda')]);
    }

    public function indicadores()
    {
        return view('cidade.indicadores', ['indicadores' => Indicadores::calcular()]);
    }

    /** @return array{situacao: string, servico: ?string} */
    private function filtrosFila(Request $request): array
    {
        $situacao = $request->query('situacao');
        $servico = $request->query('servico');

        return [
            'situacao' => in_array($situacao, ['abertos', 'atrasados', 'resolvidos', 'todos'], true) ? $situacao : 'abertos',
            'servico' => is_string($servico) && array_key_exists($servico, config('cidade.servicos')) ? $servico : null,
        ];
    }

    public function atualizar(Request $request, string $protocolo)
    {
        $this->somenteDemo($request);
        $dados = $request->validate(['status' => ['required', 'string', Rule::in(array_values(Demanda::PROXIMO))]]);
        DB::transaction(function () use ($protocolo, $dados) {
            $demanda = $this->buscar($protocolo);
            $novo = $dados['status'];
            abort_if($demanda->veioDoSp156(), 409, 'Chamados do SP156 são somente leitura neste protótipo.');
            abort_unless((Demanda::PROXIMO[$demanda->status] ?? null) === $novo, 409, 'Status já atualizado ou transição inválida.');
            $mudou = Demanda::whereKey($demanda->id)->where('status', $demanda->status)->where('origem', '!=', 'sp156')
                ->update(['status' => $novo] + ($novo === 'resolvida' ? ['finalizada_em' => now()] : []));
            abort_unless($mudou === 1, 409, 'Status já atualizado.');
            $demanda->registrarEvento($novo, true);
        });

        // Volta para a mesma visão da fila (filtros e página).
        $volta = array_filter($request->only(['situacao', 'servico', 'page']), fn ($v) => is_string($v) && $v !== '');

        return redirect()->route('secretaria', $volta)->with('aviso', 'Status atualizado. Notificação simulada registrada.');
    }

    private function buscar(string $protocolo): Demanda
    {
        abort_unless(preg_match('/^ALFA-[A-F0-9]{16}$/D', $protocolo), 404);

        return Demanda::where('protocolo', $protocolo)->firstOrFail();
    }

    private function somenteDemo(Request $request): void
    {
        AcessoSecretaria::exigir($request);
    }
}
