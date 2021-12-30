<?php namespace Modules\CupChart\Services;
/**
 * Created by PhpStorm.
 * User: pier
 * Date: 30/01/17
 * Time: 11:20
 */



//use Elasticsearch\ClientBuilder;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GeneraGrafico
{

    protected $client = null;

    public function __construct()
    {

    }
    public function creaGrafico($importazione_id) {
        $impTabelle = \App\Models\ImportazioneTabella::where('importazione_id',$importazione_id)->get();

        foreach ($impTabelle as $tabella) {
            $attributes = $this->getChartAttributes($tabella);
            if ($attributes === false) {
                $attributes = $this->defaultChartAttributes($tabella);
            }
            $metaData = json_decode($tabella->metadata, true);
            $extra = Arr::get($metaData,'extra',[]);
            $grafico = Arr::get($extra,'grafico','');
            if ($grafico != 'no'){
                //$attributes = $this->_postAttributes($tabella,$attributes);
                //Log::info('attributes ' . print_r($attributes,true) );

                $html = $this->getHtml($attributes);
                \App\Models\GraficoTabella::create([
                    'nome' => $attributes['cup-grafico'],
                    'html' => $html,
                    'attributes' => json_encode($attributes),
                    'importazione_tabelle_id' => $tabella->getKey()
                ]);
            }

            // aggiungo tabella dati
            $attributes['class'] = "table-preview";
            $attributes['cup-type'] = 'table';
            $html = $this->getHtml($attributes);
            \App\Models\GraficoTabella::create([
                'nome' =>  $attributes['cup-grafico'],
                'html' => $html,
                'attributes' => json_encode($attributes),
                'importazione_tabelle_id' => $tabella->getKey()
            ]);
        }
    }

    /**
     * metodo per sovrascrivere la generazione di default dei grafici
     * @param $tabella
     * @return void
     */
    protected function getChartAttributes($tabella) {
        return false;
    }

    protected function getHtml ($attributes) {
        if (!Arr::exists($attributes,'cup-titolo'))
            $attributes['cup-titolo'] = '';
        $html = '<div class="' . $attributes['cup-class'] . '" cup-type="' . $attributes['cup-type']  .
            '" cup-grafico="' . $attributes['cup-grafico'] . '" cup-colors="' . $attributes['cup-colors'] .
            '" cup-chart-type="' . $attributes['cup-chart-type'] . '" cup-filters="' . $attributes['cup-filters'] .
            '" cup-series="' . $attributes['cup-series'] . '" cup-titolo="' . $attributes['cup-titolo'] .
            '" cup-conf="' . $attributes['cup-conf'] . '"></div>';
        return $html;
    }

    protected function defaultChartAttributes($tabella) {
        $metaData = json_decode($tabella->metadata, true);
        $token_split_filters = config('cupparis-chart.token_split_filters',',');
        $mDot = Arr::dot($metaData);
        $cupGrafico = $tabella->elastic_id;
        $cupColors = "default";
        $cupChartType = "chart";
        $cupType = 'chart';
        $cupFilters = '';
        $cupSeries = '';
        $cupConf = 'chartConf';
        $cupTitle = $tabella->nome;
        //print_r($mDot);
//        $stop = false;
//        // series automatiche
//        $i = 0;
//        Log::info("$cupGrafico\n----analizzo series---");
//        while (!$stop) {
//            $topName = strtolower(Arr::get($mDot, "inferredSeries.top.$i.name"));
//            if (!$topName) {
//                $stop = true;
//                continue;
//            }
//            $cupSeries .= ($cupSeries?$token_split_filters:'') . $topName. ':*';
//            $i++;
//        }
        // filters automatici
        $i = 0;
        $stop = false;
        $extra = $metaData['extra'];
        $grafico = Arr::get($extra,'grafico','');
        $leftKeys = $this->_getLeftKeys($metaData);
        $topKeys = $this->_getTopKeys($metaData);

        Log::info("----analizzo filters----" . print_r($leftKeys,true) . "--- top --- " . print_r($topKeys,true));

        switch ($grafico) {
            case 'geografico':
                $cupType = 'map';
                $cupChartType = $this->_mapType($leftKeys);
//                // forzo tutte le colonne a cardinalita' 1;
//                for ($c=0;$c<count($topKeys);$c++) {
//                    $cupSeries .= ($cupSeries?$token_split_filters:'') . $topKeys[$c]. ':?';
//                }
                $cupSeries = $this->_getDefaultSeries($extra,$topKeys,'?');
                $cupColors = 'gradiente_blu';
                $cupFilters = $this->_getDefaultFilters($extra,$leftKeys);
                break;
            case 'barre_orizzontali':
                $cupChartType = "chart-o";
                $cupSeries = $this->_getDefaultSeries($extra,$topKeys);
                $cupFilters = $this->_getDefaultFilters($extra,$leftKeys);
                break;
            case 'barre_verticali':
                $cupChartType = "chart";
                $cupSeries = $this->_getDefaultSeries($extra,$topKeys);
                $cupFilters = $this->_getDefaultFilters($extra,$leftKeys);
                break;
            case 'linee':
                $cupChartType = "line";
                $cupSeries = $this->_getDefaultSeries($extra,$topKeys);
                $cupFilters = $this->_getDefaultFilters($extra,$leftKeys);
                break;
            default:
                $cupSeries = $this->_getDefaultSeries($extra,$topKeys);
                $cupFilters = $this->_getDefaultFilters($extra,$leftKeys);
                break;

        }







//        while (!$stop) {
//            $leftName = strtolower(Arr::get($mDot, "inferredSeries.left.$i.name"));
//            if (!$leftName) {
//                $stop = true;
//                continue;
//            }
//            if ($leftName == 'comune') {
//                $cupType = 'map';
//                $cupChartType = 'comuni';
//                $cupColors = 'gradiente_blu';
//                Log::info("trovato comune");
//            } else if ($leftName == 'provincia') {
//                $cupType = 'map';
//                $cupChartType = 'province';
//                $cupColors = 'gradiente_blu';
//                Log::info("trovata provincia");
//            } else if ($leftName == 'regione') {
//                $cupType = 'map';
//                $cupChartType = 'regioni';
//                $cupColors = 'gradiente_blu';
//                Log::info("trovata regione");
//            } else if ($leftName == 'nazione') {
//                $cupType = 'map';
//                $cupChartType = 'nazioni';
//                $cupColors = 'gradiente_blu';
//                Log::info("trovata nazione");
//            } else if ($leftName == 'anno') {
//                $cupChartType = 'line';
//                Log::info("trovato anno");
//            }
//            $i++;
//        }
        if ($cupType == 'map')
            $cupConf = 'mapConf';

        // se sono stati definiti dei filtri in excel sovrascrive tutti gli altri
//        if (count($metaData['extra']['filtri_top']) > 0) {
//            $cupSeries = join($token_split_filters,$metaData['extra']['filtri_top']);
//        }
//        if (count($metaData['extra']['filtri_left']) > 0) {
//            $cupFilters = join($token_split_filters,$metaData['extra']['filtri_left']);
//        }


        $attributes = [
            'cup-class' => 'chart-preview',
            'cup-type' => $cupType,
            'cup-grafico' => $cupGrafico,
            'cup-colors' => $cupColors,
            'cup-chart-type' => $cupChartType,
            'cup-filters' => $cupFilters,
            'cup-series' => $cupSeries,
            'cup-titolo' => $cupTitle,
            'cup-conf' => $cupConf,
        ];

        Log::info('default chart Attribute ' . print_r($attributes,true));
        return $attributes;
    }

    private function _getDefaultSeries($extra,$topKeys,$cardinality='*') {
        $cupSeries = '';
        $token_split_filters = config('cupparis-chart.token_split_filters',',');
        if (count($extra['filtri_top']) > 0) {
            $cupSeries = join($token_split_filters,$extra['filtri_top']);
        } else {
            //Log::info('default Series' . print_r($topKeys,true));
            for ($c=0;$c<count($topKeys);$c++) {
                $cupSeries .= ($cupSeries?$token_split_filters:'') . $topKeys[$c]. ':' . $cardinality;
            }
        }
        return $cupSeries;
    }
    private function _getDefaultFilters($extra,$leftKeys) {
        $cupFilters = '';
        $token_split_filters = config('cupparis-chart.token_split_filters',',');
        if (count($extra['filtri_left']) > 0) {
            $cupFilters = join($token_split_filters,$extra['filtri_left']);
        }
        return $cupFilters;
    }

    private function _getLeftKeys($metaData) {
        $leftKeys = [];
        foreach ($metaData['inferredSeries']['left'] as $left) {
            $leftKeys[] = $left['name'];
        }
        return $leftKeys;
    }
    private function _getTopKeys($metaData) {
        $topKeys = [];
        foreach ($metaData['inferredSeries']['top'] as $top) {
            $topKeys[] = $top['name'];
        }
        return $topKeys;
    }
    /**
     * esegue la post aggiustamento degli attributi comuni a tutti
     * @param $tabella
     * @param $attributes
     * @return void
     */
    private function _postAttributes($tabella,$attributes) {
        $token_split_filters = config('cupparis-chart.token_split_filters',',');
        $metaData = json_decode($tabella->metadata, true);
        $topKeys = Arr::get($metaData['inferredSeries'],'top');
        $topStringaKeys = explode($token_split_filters,$attributes['cup-series']);
        $extra = $metaData['extra'];
        Log::info('extra ' . print_r($extra,true) . print_r($attributes,true));
        $cupSeries = "";

        foreach ($topStringaKeys as $stringa) {
            $tmp = explode(':',$stringa);
            foreach ($topKeys as $top) {
                if ($top['name'] == $tmp[0]) {
                    $values = array_keys($top['values']);
                    if (count($values) > 1)
                        $cupSeries .= ($cupSeries?$token_split_filters:'') . $tmp[0]. ':*';
                }
            }
        }
        $attributes['cup-series'] = $cupSeries;
        return $attributes;
    }
    /**
     * controlla se contiene le parole che fanno scattare il grafico a mappa.
     * @return void
     */
    protected function _isMap($leftKeys) {
        $mapKeys = [
            'regione','provincia','comune','nazione',
            'regioni','province','comuni','nazioni'
        ];
        foreach ($leftKeys as $key) {
            if (in_array($key,$mapKeys))
                return true;
        }
        return false;
    }

    /**
     * ritorna il tipo di mappa in base alla key trovata in left
     * @return void
     */
    protected function _mapType($leftKeys) {
        $mapType = [
            'comuni' => ['comune','comuni'],
            'regioni' => ['regione','regioni'],
            'province' => ['provincia','province'],
            'nazioni' => ['nazione','nazioni']
        ];
        foreach ($leftKeys as $key) {
            foreach ($mapType as $type => $items) {
                if (in_array($key,$items))
                    return $type;
            }
        }
        return '';
    }
}
