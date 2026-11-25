<?php

namespace App\Http\Controllers;

use App\Models\Demanda;
use App\Support\AcessoSecretaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DemandaController extends Controller
{
    public function inicio()
    {
        return view('cidade.inicio', ['servicos' => config('cidade.servicos')]);
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

        return redirect()->route('acompanhar', $demanda->protocolo);
    }

    public function consultar(Request $request)
    {
        $valor = $request->input('protocolo');
        if (! is_string($valor) || ! preg_match('/^ALFA-[A-F0-9]{16}$/D', strtoupper($valor))) {
            return redirect()->route('inicio')->withErrors(['protocolo' => 'Informe um protocolo válido.'])->withInput()->withFragment('consulta');
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

        return view('cidade.secretaria', [
            'demandas' => Demanda::latest('id')->get(),
            'servicos' => config('cidade.servicos'),
            'simuladas' => DB::table('historico_status')->where('notificacao_simulada', true)->count(),
        ]);
    }

    public function atualizar(Request $request, string $protocolo)
    {
        $this->somenteDemo($request);
        $dados = $request->validate(['status' => ['required', 'string', Rule::in(array_values(Demanda::PROXIMO))]]);
        DB::transaction(function () use ($protocolo, $dados) {
            $demanda = $this->buscar($protocolo);
            $novo = $dados['status'];
            abort_unless((Demanda::PROXIMO[$demanda->status] ?? null) === $novo, 409, 'Status já atualizado ou transição inválida.');
            $mudou = Demanda::whereKey($demanda->id)->where('status', $demanda->status)->update(['status' => $novo]);
            abort_unless($mudou === 1, 409, 'Status já atualizado.');
            $demanda->registrarEvento($novo, true);
        });

        return redirect()->route('secretaria')->with('aviso', 'Status atualizado. Notificação simulada registrada.');
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
