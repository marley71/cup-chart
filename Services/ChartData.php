<?php   namespace Modules\CupChart\Services;
/**
 * Created by PhpStorm.
 * User: pier
 * Date: 30/01/17
 * Time: 11:20
 */




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

class ChartData
{

    protected $client = null;
    protected $data = null;
    protected $params = [];
    protected $filtersContext = [];
    protected $filters = [];
    protected $seriesContext = [];
    protected $series = [];
    protected $multidimensionale = true;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData($type,$params) {
        $this->params = $params;
        try {
            if ($type=='map') {
                $this->multidimensionale = false;
                return $this->_mapData();
            }
            if ($type=='chart' || $type == 'table') {
                // TODO gestire l'info multidimensionale o no
                return $this->_chartData();
            }
            throw new \Exception($type . ' type non gestito');
        } catch (\Exception $e) {
            Log::error($e->getTraceAsString());
            throw new \Exception($e->getMessage() . " " . $e->getFile() . ":" . $e->getLine());

        }

    }

    protected function _chartData() {
        $result = [];
        $this->_setFilters();
        $this->_setSeries();

        //valori colonne e il loro offset all'interno del vettore values
        $series = $this->_getKeys($this->data['series']);
        $topSeries = $series['top'];
        $leftSeries = $series['left'];

        $cartesian = $this->_getSeries($topSeries);
        $cartesianAll = $this->_getSeries($topSeries,true);

//        $rowKeys = $leftSeries[array_keys($series['left'])[0]]['values'];
//        $rowIndexKeys = array_keys($rowKeys);
        $values = [];
        $separtoreLeft = config('cupparis-chart.separatore_left');
        $maxValue = 0;
        $minValue = 0;
        $isInt = true;

        foreach ($this->data['values'] as $item) {

            if (!$this->_matchFilter($item))
                continue;
            foreach ($cartesian as $subKeys) {
                if (!$this->_matchSerieInValues($subKeys,$item)) {
                    continue;
                }
                $subKey = implode(' ',$subKeys);
                if (!Arr::exists($values,$subKey)) {
                    $values[$subKey] = [];
                }

                $floatValue = floatval($item['value']);
                $isInt |= $this->isInt($floatValue);
                $maxValue = $maxValue<$floatValue?$floatValue:$maxValue;
                $minValue = $minValue>$floatValue?$floatValue:$minValue;
                $rowLabel = [];
                // TODO riformulare in base ai filtri sulle left...
                foreach($leftSeries as $key => $lserie ) {
                    $rowLabel[] = $item[$key];
                    //$rowLabel .= ($rowLabel?" " . $item[$key]:$item[$key]);
                }
                $rowLabel = implode($separtoreLeft,$rowLabel);
                //$rowLabel = 'bo';

                if (!Arr::exists($values[$subKey],$rowLabel)) {
                    $values[$subKey][$rowLabel] = [
                        'label' => $subKey,
                        'total' => 0
                    ];
                }
                $values[$subKey][$rowLabel]['total'] += $floatValue;


            }
        }


        $ll = $series['left'][array_keys($series['left'])[0]];
        $result['measureName'] =  Arr::get($ll,'label','');
        $result['description'] = $this->data['titolo'];
        $result['values'] = $values;
        $result['context'] = $this->filtersContext;
        $result['seriesContext'] = $this->seriesContext;
        $result['leftSeries'] =$leftSeries;
        $result['topSeries'] =$topSeries;
        $result['separatoreLeft'] = $separtoreLeft;
        $result['extra'] = Arr::get($this->data,'extra',[]);
        $result['extra']['tipo'] = $isInt?'integer':'float';
        $result['min'] = $minValue<0?$minValue:0;
        // TODO questo potrebbe essere configurabile
        //$result['max'] = Arr::get($result['extra'],'tipo_valore',null) == 'percentuale'?100:$maxValue;
        $result['max'] = $maxValue;
        return $result;
    }


    protected function _mapData() {
        $result = [];
        $this->_setFilters();
        $this->_setSeries(true);
        $seriesValues = [];
        $isInt = true;
        $series = $this->_getKeys($this->data['series']);
        $topSeries = $series['top'];
        $leftSeries = $series['left'];
        $cartesian = $this->_getSeries($topSeries);
        $cartesianAll = $this->_getSeries($topSeries,true);

        $rowKeys = $leftSeries[array_keys($series['left'])[0]]['values'];
        $rowIndexKeys = array_keys($rowKeys);
        $mode = '';
        $mapKey = '';
        $mapIstat = [];
        if (array_key_exists('comune',$leftSeries)) {
            $mode = 'comuni';
            $mapKey = 'comune';
            $mapIstat = $this->_comuniIstat($leftSeries[$mapKey]['values']);
        } else if (array_key_exists('regione',$leftSeries)) {
            $mode = 'regioni';
            $mapKey = 'regione';
            $mapIstat = $this->_regioniIstat($leftSeries[$mapKey]['values']);
        } else if (array_key_exists('nazione',$leftSeries)) {
            $mode = 'nazioni';
            $mapKey = 'nazione';
            $mapIstat = $this->_nazioniIstat($leftSeries[$mapKey]['values']);
        } else if (array_key_exists('provincia',$leftSeries)) {
            $mode = 'province';
            $mapKey = 'provincia';
            $mapIstat = $this->_provinceIstat($leftSeries[$mapKey]['values']);
        }

        $values = [];

        foreach ($this->data['values'] as $item) {

            if (!$this->_matchFilter($item))
                continue;
            foreach ($cartesian as $subKeys) {
                if (!$this->_matchSerieInValues($subKeys,$item)) {
                    continue;
                }
//                if (!$this->_matchSerie($subKeys,$cartesian))
//                    continue;

                $subKey = implode(' ',$subKeys);
                if (!Arr::exists($values,$subKey)) {
                    $values[$subKey] = [];
                    $seriesValues[$subKey] = [];
                }
                $luogo = $item[$mapKey];
                $floatValue = floatval($item['value']);
                $isInt |= $this->isInt($floatValue);
                switch ($mode) {
                    case 'comuni':
                    case 'regioni':
                    case 'nazioni':
                    case 'province':
                        if (Arr::get($mapIstat,$luogo))  {
                            $regioneIstat = $mapIstat[$luogo];
                            $seriesValues[$subKey][] = $floatValue;
                            //$min = ($min>$floatValue)?$floatValue:$min;
                            //$max = ($max<$floatValue)?$floatValue:$max;

                            if (!Arr::exists($values[$subKey],$luogo)) {
                                $values[$subKey][$luogo] = [
                                    'comune' => $luogo,
                                    'total' => 0
                                ];
                            }
                            $values[$subKey][$luogo]['total'] += $floatValue;
                        } else {
                            Log::notice("ChartData luogo non trovato $luogo modalita $mode");
                        }
                        break;
                }


            }
        }

        $result['range'] = $this->_calcolaIntervalli($seriesValues);
        $result['context'] = $this->filtersContext;
        $result['seriesContext'] = $this->seriesContext;
        $result['measureName'] = $mapKey;
        $result['description'] = $this->data['titolo'];
        $result['values'] = $values;
        $result['sort'] = $seriesValues;
        //$result['step'] = $step;
        $result['leftSeries'] =$leftSeries;
        $result['topSeries'] =$topSeries;
        $result['extra'] = Arr::get($this->data,'extra',[]);
        $result['extra']['tipo'] = $isInt?'integer':'float';
        return $result;
    }


    /**
     * setta i filtri dei dati da recupare la convenzione e' questa funzione lavora sulle left series
     * 1) * tutti di dati in caso di chiavi diverse vengono sommati
     * 2) *-key tutti i dati filtrati per key
     * 3) ? prendere solo i dati del primo raggruppamento della left
     * 4) ?-key prendere solo i dati della chiave indicata
     */
    protected function _setFilters() {
        $queryParams = Arr::get($this->params,'filters',[]);
        $this->filters = [];
        $this->filtersContext = [];
        foreach ($queryParams as $key => $query) {
            $filterValues =  $this->data['series'][$key]['values'];
            if (substr($query,0,1) == "*") {
                $filterValues['*'] = 'Tutti ';
                $this->filtersContext[$key] = [
                    'value' => '*',
                    'domainValues' => $filterValues
                ];
                if ($query == '*') { // il filtro e' tutto quindi i valori verranno sommati come se non fosse definito
                    ;
                }
                if (substr($query,0,2) == "*-") {
                    $selectValue = substr($query,2);
                    $this->filters[$key] = $selectValue;
                }
                continue;
            }
            if (substr($query,0,1) == "?") {
                if ($query == '?') {// i valori del filtro non possono essere sommato prendo il primo valore valido del filtro
                    $this->filtersContext[$key] = [
                        'value' => array_keys($filterValues)[0],
                        'domainValues' => $filterValues
                    ];
                    $this->filters[$key] = $filterValues[array_keys($filterValues)[0]];
                }
                if (substr($query,0,2) == "?-") {
                    $selectValue = substr($query,2);
                    $this->filtersContext[$key] = [
                        'value' => $selectValue,
                        'domainValues' => $filterValues
                    ];
                    $this->filters[$key] = $selectValue;
                }
            } else {
                $this->filters[$key] = $query;
            }
        }
    }

    protected function _setSeries($isMap = false) {
        $queryParams = Arr::get($this->params,'series',[]);
        $this->series = [];
        $this->seriesContext = [];


        foreach ($queryParams as $key => $query) {
            if (is_string($query)) {
                if (substr($query,0,1) == "*") {
                    $selectValues =
                    if ($query == '*') { // il filtro e' tutto quindi i valori verranno sommati come se non fosse definito
                        // nel caso di mappa il concetto di visualizza tutti non ha senso, si mostra un solo valore per volta
                        if ($isMap) {
                            // prendo la serie e lo imposto al primo valore del dominio
                            $this->seriesContext[$key] = [
                                'value' => array_keys($filterValues)[0],
                                'domainValues' => $filterValues
                            ];
                            $this->series[$key] = $filterValues[array_keys($filterValues)[0]];
                        } else {
                            $filterValues['*'] = 'Tutti ';
                            $this->seriesContext[$key] = [
                                'value' => '*',
                                'domainValues' => $filterValues
                            ];
                        }
                    }
                    if (substr($query,0,2) == "*-") {
                        $selectValue = substr($query,2);
                        $this->filters[$key] = $selectValue;
                        $this->seriesContext[$key] = [
                            'value' => $selectValue,
                            'domainValues' => $filterValues
                        ];
                    }
                    continue;
                }
                if (substr($query,0,1) == "?") {
                    if ($query == '?') {// i valori del filtro non possono essere sommate prendo il primo valore valido del filtro
                        $this->seriesContext[$key] = [
                            'value' => array_keys($filterValues)[0],
                            'domainValues' => $filterValues
                        ];
                        $this->series[$key] = $filterValues[array_keys($filterValues)[0]];
                    }
                    if (substr($query,0,2) == "?-") {
                        $selectValue = substr($query,2);
                        $this->filtersContext[$key] = [
                            'value' => $selectValue,
                            'domainValues' => $filterValues
                        ];
                        $this->filters[$key] = $selectValue;
                    }
                } else {
                    $this->series[$key] = $query;
                }
            }





            
            // multidimensionali






        }
    }


    protected function _setSerieMultidimensionale() {
        $queryParams = Arr::get($this->params,'series',[]);
        $this->series = [];
        $this->seriesContext = [];
        foreach ($queryParams as $key => $query) {
            $filterValues =  $this->data['series'][$key]['values'];
            // multidimensionali

            if (substr($query,0,1) == "*") {
                if ($query == '*') { // il filtro e' tutto quindi i valori verranno sommati come se non fosse definito
                    // nel caso di mappa il concetto di visualizza tutti non ha senso, si mostra un solo valore per volta
                    if (!$this->multidimensionale) {
                        $this->seriesContext[$key] = [
                            'value' => array_keys($filterValues),
                            'domainValues' => $filterValues
                        ];

                    } else {
                        // prendo la serie e lo imposto al primo valore del dominio
                        $this->seriesContext[$key] = [
                            'value' => array_keys($filterValues)[0],
                            'domainValues' => $filterValues
                        ];
                        $this->series[$key] = $filterValues[array_keys($filterValues)[0]];
                    }
                }
                if (substr($query,0,2) == "*-") {
                    $selectValue = substr($query,2);
                    $this->filters[$key] = $selectValue;
                    $this->seriesContext[$key] = [
                        'value' => $selectValue,
                        'domainValues' => $filterValues
                    ];
                }
                continue;
            }
            if (substr($query,0,1) == "?") {
                if ($query == '?') {// i valori del filtro non possono essere sommate prendo il primo valore valido del filtro
                    $this->seriesContext[$key] = [
                        'value' => array_keys($filterValues)[0],
                        'domainValues' => $filterValues
                    ];
                    $this->series[$key] = $filterValues[array_keys($filterValues)[0]];
                }
                if (substr($query,0,2) == "?-") {
                    $selectValue = substr($query,2);
                    $this->filtersContext[$key] = [
                        'value' => $selectValue,
                        'domainValues' => $filterValues
                    ];
                    $this->filters[$key] = $selectValue;
                }
            } else {
                $this->series[$key] = $query;
            }
        }
    }

    protected function _setSerieMonodimensionale() {
        $queryParams = Arr::get($this->params,'series',[]);
        $this->series = [];
        $this->seriesContext = [];
        foreach ($queryParams as $key => $query) {
            $filterValues =  $this->data['series'][$key]['values'];
            // multidimensionali





            if (substr($query,0,1) == "*") {
                if ($query == '*') { // il filtro e' tutto quindi i valori verranno sommati come se non fosse definito
                    // nel caso di mappa il concetto di visualizza tutti non ha senso, si mostra un solo valore per volta
                    if ($isMap) {
                        // prendo la serie e lo imposto al primo valore del dominio
                        $this->seriesContext[$key] = [
                            'value' => array_keys($filterValues)[0],
                            'domainValues' => $filterValues
                        ];
                        $this->series[$key] = $filterValues[array_keys($filterValues)[0]];
                    } else {
                        $filterValues['*'] = 'Tutti ';
                        $this->seriesContext[$key] = [
                            'value' => '*',
                            'domainValues' => $filterValues
                        ];
                    }
                }
                if (substr($query,0,2) == "*-") {
                    $selectValue = substr($query,2);
                    $this->filters[$key] = $selectValue;
                    $this->seriesContext[$key] = [
                        'value' => $selectValue,
                        'domainValues' => $filterValues
                    ];
                }
                continue;
            }
            if (substr($query,0,1) == "?") {
                if ($query == '?') {// i valori del filtro non possono essere sommate prendo il primo valore valido del filtro
                    $this->seriesContext[$key] = [
                        'value' => array_keys($filterValues)[0],
                        'domainValues' => $filterValues
                    ];
                    $this->series[$key] = $filterValues[array_keys($filterValues)[0]];
                }
                if (substr($query,0,2) == "?-") {
                    $selectValue = substr($query,2);
                    $this->filtersContext[$key] = [
                        'value' => $selectValue,
                        'domainValues' => $filterValues
                    ];
                    $this->filters[$key] = $selectValue;
                }
            } else {
                $this->series[$key] = $query;
            }
        }
    }

    protected function _matchFilter($values) {
        foreach ($this->filters as $keyFilter => $filter) {
            if ($values[$keyFilter] != $filter)
                if (strcasecmp ( trim($values[$keyFilter]), trim($filter) ) != 0)
                    return false;
        }
        return true;
    }
    protected function _matchSerieInValues($validSerie,$values) {
        $found = true;
        foreach ($validSerie as $keySerie => $valueSerie) {
            if ($values[$keySerie] == $valueSerie)
                $found &= true;
            else
                $found &= false;
        }
        if ($found)
            return true;
        return false;
    }

    protected function _matchSerie($currentSerie,$validSerie) {
        foreach ($validSerie as $valid) {
            $found = true;
            foreach ($currentSerie as $key => $value) {
                if ($valid[$key] == $value)
                    $found &= true;
                else
                    $found &= false;
            }
            if ($found)
                return true;
        }
        return false;
    }
    protected function _getKeys($series) {
        $topSeries = [];
        $leftSeries = [];
        foreach ($series as $key => $serie) {
            if ($serie['type'] == 'top')
                $topSeries[$key] = $serie;
            else
                $leftSeries[$key] = $serie;
        }
        return [
            'top' => $topSeries,
            'left' => $leftSeries
        ];
    }

    protected function _comuniIstat($comuni) {
        $istat = [];
        foreach ($comuni as $comune) {
            $codiceIstat = CupGeoComune::where('nome_it',$comune)->first();
            if ($codiceIstat) {
                $codiceIstat = $codiceIstat->codice_istat;
            } else
                $codiceIstat = null;
            $istat[$comune] = $codiceIstat;
        }
        return $istat;
    }

    protected function _regioniIstat($regioni) {
        $istat = [];
        foreach ($regioni as $regione) {
            $codiceIstat = CupGeoRegione::where('nome_it',$regione)->first();
            if ($codiceIstat) {
                $codiceIstat = $codiceIstat->nome_it;
            } else
                $codiceIstat = $regione;
            $istat[$regione] = $codiceIstat;
        }
        return $istat;
    }

    protected function _provinceIstat($province) {
        $istat = [];
        foreach ($province as $provincia) {
            $codiceIstat = CupGeoProvincia::where('nome_it',$provincia)->first();
            if ($codiceIstat) {
                $codiceIstat = $codiceIstat->nome_it;
            } else
                $codiceIstat = $provincia;
            $istat[$provincia] = $codiceIstat;
        }
        return $istat;
    }

    protected function _nazioniIstat($nazioni) {
        $istat = [];
        foreach ($nazioni as $nazione) {
            $codiceIstat = CupGeoNazione::where('nome_it',$nazione)->first();
            if ($codiceIstat) {
                $codiceIstat = $codiceIstat->nome_it;
            } else
                $codiceIstat = $nazione;
            $istat[$nazione] = $codiceIstat;
        }
        return $istat;
    }

    protected function _getSeries($series,$all=false) {
        $values = [];
        foreach ($series as $serieName => $serie) {
            if ($all) {
                $values[] = array_keys($serie['values']);
                continue;
            }

            if (Arr::exists($this->series,$serieName) ) {
                if ($this->series[$serieName] != '*') {
                    $values[] = [$serie['values'][$this->series[$serieName]] ];
                }
            } else
                $values[] = array_keys($serie['values']);
        }
        $cartesian = $this->_cartesian($values);
        $keys = array_keys($series);
        $cartesianAssoc = [];
        foreach ($cartesian as $items) {
            $tmp = [];
            foreach ($items as $i => $item) {
                $tmp[$keys[$i]] = $item;
            }
            $cartesianAssoc[] = $tmp;
        }
        return $cartesianAssoc;
    }

    protected function _cartesian($array) {
        if (!$array) {
            return array(array());
        }

        $subset = array_shift($array);
        $cartesianSubset = $this->_cartesian($array);

        $result = array();
        foreach ($subset as $value) {
            foreach ($cartesianSubset as $p) {
                array_unshift($p, $value);
                $result[] = $p;
            }
        }

        return $result;
    }

    protected function _calcolaIntervalli($seriesValues) {
        $interval = [];

        foreach ($seriesValues as $key => $val) {
            $valid = array_unique($seriesValues[$key]);
            sort($valid);
            $lun = count($valid);
            if ($lun <4) {
                $min = $valid[0];
                $mins = [];
                for ($i=0;$i<4-$lun;$i++) {
                    $mins[] = $min;
                }
                $interval[$key] = array_merge($mins,$valid);
            } else {
                $step = floor(count($valid) / 4.0);
                $interval[$key] = [];
                for ($i=0;$i<4;$i++) {
                    // TODO controllare il calcolo del range non funziona bene con valori ripetutti troppe volte
                    //            if ($i>0 &&
                    //                ($seriesValues[$i*$step]  == $seriesValues[($i-1) * $step]) )
                    //                continue;
                    $interval[$key][] = $valid[$i*$step];
                }
            }

        }
        return $interval;
    }

    private function _getValidFilterValue($query,$filters) {

    }

    private function isInt($value) {
        if ((int) $value == $value) {
           return true;
        }
        return false;
    }
}
