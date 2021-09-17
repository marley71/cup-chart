<?php namespace Modules\CupChart\Models;


use App\Services\Importazione\RenderTableService;
use Gecche\Cupparis\App\Breeze\Breeze;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Breeze (Eloquent) model for T_AREA table.
 */
class GraficoTabella extends Breeze
{

//    use ModelWithUploadsTrait;

    protected $table = 'grafici_tabelle';

    protected $guarded = ['id'];

    public $timestamps = false;
    public $ownerships = false;

    public $appends = [

    ];

    protected $casts = [

    ];

    public static $relationsData = [

        'importazione_tabella' => [self::BELONGS_TO, 'related' => \App\Models\ImportazioneTabella::class, 'table' => 'importazioni_tabelle', 'foreignKey' => 'importazione_tabelle_id'],


//        'belongsto' => array(self::BELONGS_TO, Area::class, 'foreignKey' => '<FOREIGNKEYNAME>'),
//        'belongstomany' => array(self::BELONGS_TO_MANY, Area::class, 'table' => '<TABLEPIVOTNAME>','pivotKeys' => [],'foreignKey' => '<FOREIGNKEYNAME>','otherKey' => '<OTHERKEYNAME>') ,
//        'hasmany' => array(self::HAS_MANY, Area::class, 'table' => '<TABLENAME>','foreignKey' => '<FOREIGNKEYNAME>'),
    ];

    public static $rules = [
//        'username' => 'required|between:4,255|unique:users,username',
    ];

    public $columnsForSelectList = ['nome'];
     //['id','nome'];

    public $defaultOrderColumns = [];
     //['cognome' => 'ASC','nome' => 'ASC'];

    public $columnsSearchAutoComplete = ['nome'];
     //['cognome','denominazione','codicefiscale','partitaiva'];

    public $nItemsAutoComplete = 20;
    public $nItemsForSelectList = 100;
    public $itemNoneForSelectList = false;
    public $fieldsSeparator = ' - ';


    public function getMetadataoAttribute() {
        return json_encode($this->metadata,JSON_PRETTY_PRINT);
    }

    public function getGraphKeyAttribute() {
        return $this->importazione_id . "_" . $this->sheetname . "_" . $this->progressivo;
    }

    public function getTabellaExcelAttribute() {
        $renderTableService = new RenderTableService($this);
        return $renderTableService->getHtmlFromMetadata();
    }


    public static function creaGrafico($menu_id,$importazione_id) {
        $impTabelle = \App\Models\ImportazioneTabella::where('importazione_id',$importazione_id)->get();
        switch ($menu_id) {
            default:
                foreach ($impTabelle as $tabella) {
                    $mDot = Arr::dot(json_decode($tabella->metadata, true));
                    $cupGrafico = $tabella->elastic_id;
                    $cupColors = "default";
                    $cupChartType = "chart";
                    $cupType = 'chart';
                    $cupFilters = '';
                    $cupSeries = '';
                    $cupTitle = $tabella->nome;
                    //print_r($mDot);
                    $stop = false;
                    // series automatiche
                    $i = 0;
                    Log::info("$cupGrafico\n----analizzo series---");
                    while (!$stop) {
                        $topName = strtolower(Arr::get($mDot, "inferredSeries.top.$i.name"));
                        if (!$topName) {
                            $stop = true;
                            continue;
                        }
                        if (array_search($topName, ['sesso', 'eta', 'sostanza', 'detenuti', 'nazionalita']) !== FALSE) {
                            $cupSeries .= ($cupSeries ? ',' : '') . $topName . ':*';
                        }
                        $i++;
                    }
                    // filters automatici
                    $i = 0;
                    $stop = false;
                    Log::info("----analizzo filters----");
                    while (!$stop) {
                        $leftName = strtolower(Arr::get($mDot, "inferredSeries.left.$i.name"));
                        if (!$leftName) {
                            $stop = true;
                            continue;
                        }
                        if ($leftName == 'comune') {
                            $cupType = 'map';
                            $cupChartType = 'comuni';
                            $cupColors = 'gradiente_blu';
                            Log::info("trovato comune");
                        } else if ($leftName == 'provincia') {
                            $cupType = 'map';
                            $cupChartType = 'province';
                            $cupColors = 'gradiente_blu';
                            Log::info("trovata regione");
                        } else if ($leftName == 'regione') {
                            $cupType = 'map';
                            $cupChartType = 'regioni';
                            $cupColors = 'gradiente_blu';
                            Log::info("trovata regione");
                        } else if ($leftName == 'nazione') {
                            $cupType = 'map';
                            $cupChartType = 'nazioni';
                            $cupColors = 'gradiente_blu';
                            Log::info("trovata regione");
                        } else if ($leftName == 'anno') {
                            $cupChartType = 'line';
                            Log::info("trovato anno");
                        }
//
// else if ($leftName == 'sostanza') {
//                            $cupFilters .= ($cupFilters?',':'') . 'sostanza:*';
//                            echo "trovato sostanza\n";
//                        }
                        $i++;
                    }
                    $html = "<div class=\"chart-preview\" cup-type=\"$cupType\" cup-grafico=\"$cupGrafico\"
                                cup-colors=\"$cupColors\" cup-chart-type=\"$cupChartType\"
                                 cup-filters=\"$cupFilters\" cup-series=\"$cupSeries\" cup-titolo=\"$cupTitle\">
                            </div>";
                    \App\Models\GraficoTabella::create([
                        'nome' => $cupGrafico,
                        'html' => $html,
                        'importazione_tabelle_id' => $tabella->getKey()
                    ]);

                    // aggiungo tabella dati
                    $html = "<div class=\"table-preview\" cup-type=\"table\" cup-grafico=\"$cupGrafico\"
                                cup-colors=\"$cupColors\" cup-chart-type=\"$cupChartType\"
                                 cup-filters=\"$cupFilters\" cup-series=\"$cupSeries\" cup-titolo=\"$cupTitle\">
                            </div>";
                    \App\Models\GraficoTabella::create([
                        'nome' => $cupGrafico,
                        'html' => $html,
                        'importazione_tabelle_id' => $tabella->getKey()
                    ]);
                }
                break;
        }
    }

    public static function getHtml ($params) {
        if (!Arr::exists($params,'cup-titolo'))
            $params['cup-titolo'] = '';
        $html = '<div class="' . $params['cup-class'] . '" cup-type="' . $params['cup-type']  .
            '" cup-grafico="' . $params['cup-grafico'] . '" cup-colors="' . $params['cup-colors'] .
            '" cup-chart-type="' . $params['cup-chart-type'] . '" cup-filters="' . $params['cup-filters'] .
            '" cup-series="' . $params['cup-series'] . '" cup-titolo="' . $params['cup-titolo'] . '"></div>';
        return $html;
    }
}
