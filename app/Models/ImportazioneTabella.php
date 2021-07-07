<?php

namespace App\Models;

use App\Services\Importazione\RenderTableService;
use Gecche\Cupparis\App\Breeze\Breeze;

/**
 * Breeze (Eloquent) model for T_AREA table.
 */
class ImportazioneTabella extends \Modules\CupChart\Models\ImportazioneTabella
{
	use Relations\ImportazioneTabellaRelations;


}
