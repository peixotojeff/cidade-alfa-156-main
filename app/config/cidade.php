<?php

return [
    'demo' => env('CIDADE_DEMO', false),
    // Acesso à /secretaria fora do loopback.
    // - aberta=true: painel sem login (só para demonstração).
    // - senão, HTTP Basic Auth com usuário e senha; vazio = desativada (404).
    'secretaria' => [
        'aberta' => filter_var(env('SECRETARIA_ABERTA', false), FILTER_VALIDATE_BOOL),
        'usuario' => env('SECRETARIA_USUARIO'),
        'senha' => env('SECRETARIA_SENHA'),
    ],
    // Notebook da Fase 4 (EDA) no GitHub: base dos links "Abrir no Colab" e "Ver no GitHub".
    'notebook_eda' => 'peixotojeff/cidade-alfa-156-main/blob/main/fase4/eda_sp156.ipynb',

    // Data de corte de cada planilha do SP156 (trimestre de abertura => última finalização
    // registrada no arquivo). Os status "Aberto" valem para essa data, não para hoje: o
    // atraso desses chamados é medido contra ela (ver Demanda::referenciaPrazo).
    'sp156_corte' => [
        '2026-1' => '2026-08-06',
        '2026-2' => '2026-08-06',
        '2026-3' => '2026-10-08',
    ],
    'servicos' => [
        // 'dias' é o prazo (SLA) definido pela Cidade Alfa — fictício, escolhido olhando os tempos reais
        // de resolução da amostra do SP156 (ver DESIGN.md e database/seeders/data/README.md).
        'via' => ['nome' => 'Buraco na via', 'secretaria' => 'Obras', 'dias' => 7, 'icone' => 'cone', 'exemplo' => 'Buraco, afundamento ou asfalto solto'],
        'residuos' => ['nome' => 'Entulho e descarte irregular', 'secretaria' => 'Limpeza Urbana', 'dias' => 5, 'icone' => 'folha', 'exemplo' => 'Entulho, lixo ou móveis largados na rua'],
        'bueiro' => ['nome' => 'Limpeza de bueiro', 'secretaria' => 'Limpeza Urbana', 'dias' => 7, 'icone' => 'gota', 'exemplo' => 'Boca de lobo entupida ou com mau cheiro'],
        'calcada' => ['nome' => 'Guias e sarjetas', 'secretaria' => 'Obras', 'dias' => 20, 'icone' => 'guia', 'exemplo' => 'Guia quebrada, sarjeta danificada ou afundada'],
        'arvore' => ['nome' => 'Poda ou remoção de árvore', 'secretaria' => 'Meio Ambiente', 'dias' => 30, 'icone' => 'arvore', 'exemplo' => 'Galho em risco de cair ou árvore precisando de poda'],
        'veiculo' => ['nome' => 'Veículo abandonado', 'secretaria' => 'Mobilidade', 'dias' => 30, 'icone' => 'carro', 'exemplo' => 'Carro ou carcaça parado há muito tempo na rua'],
        'iluminacao' => ['nome' => 'Iluminação pública', 'secretaria' => 'Serviços Urbanos', 'dias' => 3, 'icone' => 'lampada', 'exemplo' => 'Poste apagado, piscando ou lâmpada quebrada'],
    ],
];
