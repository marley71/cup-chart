<?php namespace App\Console\Commands;


use App\Models\Importazione;
use App\Services\Importazione\SplitStrictTablesService;
use App\Services\Importazione\SplitTablesService;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ManageImportazione extends \Modules\CupChart\Console\Commands\ManageImportazione
{

}
