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

class DataToElastic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gap:data-to-elastic {index : index elastic name} {json : file dati in formato json} {--dir} {--delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Salva un json in elastic ';

    protected $es = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->es = new ElasticSearch();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $index = $this->argument('index');
        $mapping = $this->_getMapping();
        if (!$this->es->exist($index)) {
            $this->es->create($index,$mapping);
        }
        else {
            if ($this->option('delete')) {
                echo "-- eliminio e ricreo $index \n";
                $this->es->delete($index);
                $this->es->create($index,$mapping);
            }

        }

        //
        if ($this->option('dir')) {
            $this->loadFromDir();
            return ;
        }
        $data = $this->_getData();
        $this->es->store($index,$data);

    }

    protected function loadFromDir() {
        $dir = $this->argument('json');
        $index = $this->argument('index');
        if ($handle = opendir($dir)) {

            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {

                    echo "$dir/$entry\n";
                    $jsonFile = "$dir/$entry";
                    $dataString = file_get_contents($jsonFile);
                    $this->es->store($index,$dataString);

                }
            }

            closedir($handle);
        }
        return ;
    }

    protected function _getData() {
        $jsonFile = $this->argument('json');
        $dataString = file_get_contents($jsonFile);
        return json_decode($dataString,true);
    }

    protected function _getMapping() {
        $map = [
            'properties' => [
                'titolo' => [
                    'type' => 'text'
                ],
                'sheetname' => [
                    'type' => 'text'
                ],
                'columns' => [
                    'type' => 'integer'
                ],
                'rows' => [
                    'type' => 'integer',
                ],
                'series' => [
                    'type' => 'object'
                ],
                'values' => [
                    'type' => 'object'
                ]
            ]
        ];
        return $map;
    }
}


//$table = [
//    'description' => 'Tabella1',
//    'values' => [
//        [
//            'id' => '0_0',
//            'comune' => 'pescara',
//            'codice_istat' => '068028',
//            'sesso' => 'maschio',
//            'value' => '50'
//        ],
//        [
//            'id' => '0_1',
//            'comune' => 'pescara',
//            'codice_istat' => '068028',
//            'sesso' => 'femmina',
//            'value' => '55'
//        ],
//        [
//            'id' => '1_0',
//            'comune' => 'chieti',
//            'codice_istat' => '069022',
//            'sesso' => 'maschio',
//            'value' => '20'
//        ],
//        [
//            'id' => '1_1',
//            'comune' => 'chieti',
//            'codice_istat' => '069022',
//            'sesso' => 'femmina',
//            'value' => '80'
//        ],
//        [
//            'enne' => 1,
//            'anno' => 2019,
//            'canale vendita' => 'Fisico',
//            'Regione' => 'abruzzo',
//            'Provincia'=> 'chieti',
//            'comune' => 'altino',
//            'codice_istat' => 29320,
//            'Gioco' => 'AWP',
//            'tipo di soldi' => 'Giocato',
//            'value' => 1661882,83
//        ]
//    ]
//];
