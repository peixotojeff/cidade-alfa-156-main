<?php

return [
    'demo' => env('CIDADE_DEMO', false),
    // Acesso à /secretaria fora do loopback (HTTP Basic Auth). Vazio = desativada.
    'secretaria' => [
        'usuario' => env('SECRETARIA_USUARIO'),
        'senha' => env('SECRETARIA_SENHA'),
    ],
    'servicos' => [
        'iluminacao' => ['nome' => 'Iluminação pública', 'secretaria' => 'Serviços Urbanos', 'dias' => 3],
        'via' => ['nome' => 'Buraco na via', 'secretaria' => 'Obras', 'dias' => 7],
        'residuos' => ['nome' => 'Descarte irregular', 'secretaria' => 'Meio Ambiente', 'dias' => 5],
    ],
];
