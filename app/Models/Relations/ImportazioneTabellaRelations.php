<?php

namespace App\Models\Relations;

trait ImportazioneTabellaRelations
{

    public function importazione() {

        return $this->belongsTo('App\Models\Importazione', 'importazione_id', null, null);

    }

    public function grafici() {

        return $this->hasMany('App\Models\GraficoTabella', 'importazione_tabelle_id', null);

    }



}
