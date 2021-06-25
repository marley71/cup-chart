<?php
/**
 * Created by PhpStorm.
 * User: pier
 * Date: 30/01/17
 * Time: 11:20
 */

namespace App\Services;


//use Elasticsearch\ClientBuilder;
use App\Models\CupGeoComune;
use App\Models\CupGeoNazione;
use App\Models\CupGeoProvincia;
use App\Models\CupGeoRegione;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ChartData extends \Modules\CupChart\Services\ChartData
{

}
