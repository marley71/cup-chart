<?php

return [
    // separatare usato per i valori per tabelle che hanno series left > 1
    'separatore_left' => '&&&',
    'separatore_top' => '&&&',
    'separatore_filtri_top' => ',',
    'token_split_filters' => "###",
    // inserire le chiavi nella tabella che attivano il tipo di grafico mappa, caso di default se incontro comunue come key allora il grafico geografico e ti dipo comuni
    // il cechk e' case insensitive
    'sinonimi_tipo_geografico' => [
        'comuni' => ['comune','comuni'],
        'regioni' => ['regione','regioni'],
        'province' => ['provincia','province'],
        'nazioni' => ['nazione','nazioni'],
    ]
];
