<?php

namespace App\Models;

use Gecche\Cupparis\App\Breeze\Breeze;
use Gecche\Cupparis\App\Models\UploadableTraits;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * Breeze (Eloquent) model for T_AREA table.
 */
class Importazione extends \Modules\CupChart\Models\Importazione
{
	use Relations\ImportazioneRelations;

}
