<?php

namespace App\Console\Commands;

use App\Models\LuogoMappato;
use App\Models\LuogoSensibile;
use App\Models\Variabile;
use App\Services\ElasticSearch;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DataToElastic extends \Modules\CupChart\Console\Commands\DataToElastic
{

}
