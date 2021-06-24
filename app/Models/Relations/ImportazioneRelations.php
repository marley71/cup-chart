<?php

namespace App\Models\Relations;

trait ImportazioneRelations
{

    public function tabelle() {

        return $this->hasMany('App\Models\ImportazioneTabella', 'importazione_id', null);

    }

//    public function menu() {
//
//        return $this->belongsTo('App\Models\Menu', 'menu_id', null, null);
//
//    }
//
//    public function fonte() {
//
//        return $this->belongsTo('App\Models\Fonte', 'fonte_id', null, null);
//
//    }



}
