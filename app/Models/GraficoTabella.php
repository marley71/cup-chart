<?php

namespace App\Models;

use App\Services\Importazione\RenderTableService;
use Gecche\Cupparis\App\Breeze\Breeze;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Breeze (Eloquent) model for T_AREA table.
 */
class GraficoTabella extends \Modules\CupChart\Models\GraficoTabella
{
	use Relations\GraficoTabellaRelations;

}
