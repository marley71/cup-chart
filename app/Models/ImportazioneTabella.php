<?php

namespace App\Models;

use App\Services\Importazione\RenderTableService;
use Gecche\Cupparis\App\Breeze\Breeze;

/**
 * Breeze (Eloquent) model for T_AREA table.
 */
class ImportazioneTabella extends Breeze
{
	use Relations\ImportazioneTabellaRelations;


//    use ModelWithUploadsTrait;

    protected $table = 'importazioni_tabelle';

    protected $guarded = ['id'];

    public $timestamps = false;
    public $ownerships = false;

    public $appends = [
         'graph_key' //'metadatao',
    ];

//    protected $casts = [
//        'metadata' => 'array',
//    ];

    public static $relationsData = [

        'importazione' => [self::BELONGS_TO, 'related' => \App\Models\Importazione::class, 'table' => 'importazioni', 'foreignKey' => 'importazione_id'],
        'grafici' => array(self::HAS_MANY, 'related' => GraficoTabella::class, 'table' => 'grafici_tabella','foreignKey' => 'importazione_tabelle_id'),

//        'belongsto' => array(self::BELONGS_TO, Area::class, 'foreignKey' => '<FOREIGNKEYNAME>'),
//        'belongstomany' => array(self::BELONGS_TO_MANY, Area::class, 'table' => '<TABLEPIVOTNAME>','pivotKeys' => [],'foreignKey' => '<FOREIGNKEYNAME>','otherKey' => '<OTHERKEYNAME>') ,
//        'hasmany' => array(self::HAS_MANY, Area::class, 'table' => '<TABLENAME>','foreignKey' => '<FOREIGNKEYNAME>'),
    ];

    public static $rules = [
//        'username' => 'required|between:4,255|unique:users,username',
    ];

    public $columnsForSelectList = ['nome'];
     //['id','nome'];

    public $defaultOrderColumns = ['importazione_id' => 'ASC','sheetname' => 'ASC', 'progressivo' => 'ASC'];
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


}
