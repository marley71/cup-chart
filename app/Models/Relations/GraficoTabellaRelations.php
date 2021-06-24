<?php

namespace App\Models\Relations;

trait GraficoTabellaRelations
{

    public function importazione_tabella() {

        return $this->belongsTo('App\Models\ImportazioneTabella', 'importazione_tabelle_id', null, null);
    
    }



}
