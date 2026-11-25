<?php

namespace App\Http\Controllers;

use App\Models\Demanda;
use App\Support\AcessoSecretaria;
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

        return redirect()->route('acompanhar', strtoupper($valor));
    }

    public function acompanhar(string $protocolo)
    {
        $demanda = $this->buscar($protocolo);

        return response()->view('cidade.acompanhar', [
            'demanda' => $demanda,
            'servico' => config('cidade.servicos')[$demanda->servico],
            'historico' => $demanda->historico()->orderBy('id')->get(),
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function secretaria(Request $request)
    {
        $this->somenteDemo($request);

        $servicos = config('cidade.servicos');
        $filtros = $this->filtrosFila($request);

        // Fila de trabalho: por padrão, os abertos com o prazo mais próximo (ou mais estourado) primeiro.
        $consulta = Demanda::query()
            ->when($filtros['servico'], fn ($q, $s) => $q->where('servico', $s))
            ->when($filtros['situacao'] === 'abertos', fn ($q) => $q->where('status', '!=', 'resolvida'))
            ->when($filtros['situacao'] === 'atrasados', fn ($q) => $q->where('status', '!=', 'resolvida')->where('prazo', '<', now()))
            ->when($filtros['situacao'] === 'resolvidos', fn ($q) => $q->where('status', 'resolvida'));
        $consulta = $filtros['situacao'] === 'resolvidos' || $filtros['situacao'] === 'todos'
            ? $consulta->latest('created_at')->latest('id')
            : $consulta->orderBy('prazo')->orderBy('id');

        return view('cidade.secretaria', [
            'demandas' => $consulta->paginate(15)->withQueryString(),
            'filtros' => $filtros,
            'indicadores' => Indicadores::calcular(),
            'contagem' => Demanda::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
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
            abort_unless((Demanda::PROXIMO[$demanda->status] ?? null) === $novo, 409, 'Status já atualizado ou transição inválida.');
            $mudou = Demanda::whereKey($demanda->id)->where('status', $demanda->status)
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
