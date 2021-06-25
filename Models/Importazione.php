<?php namespace Modules\CupChart\Models;


use Gecche\Cupparis\App\Breeze\Breeze;
use Gecche\Cupparis\App\Models\UploadableTraits;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * Breeze (Eloquent) model for T_AREA table.
 */
class Importazione extends Breeze
{

	use UploadableTraits;

	protected $dir = 'importazioni';
    protected $prefix = 'importazione';

//    use ModelWithUploadsTrait;

    protected $table = 'importazioni';

    protected $guarded = ['id'];

    public $timestamps = true;
    public $ownerships = true;

    public $appends = [
        'url',
    ];

    protected $casts = [
        'data' => 'array',
    ];


    public static $relationsData = [

        'tabelle' => [self::HAS_MANY, 'related' => \App\Models\ImportazioneTabella::class, 'foreignKey' => 'importazione_id'],
//        'menu' => [self::BELONGS_TO, 'related' =>  Menu::class, 'foreignKey' => 'menu_id'],
//        'fonte' => [self::BELONGS_TO, 'related' =>  Fonte::class, 'foreignKey' => 'fonte_id'],
//        'belongsto' => array(self::BELONGS_TO, Area::class, 'foreignKey' => '<FOREIGNKEYNAME>'),
//        'belongstomany' => array(self::BELONGS_TO_MANY, Area::class, 'table' => '<TABLEPIVOTNAME>','pivotKeys' => [],'foreignKey' => '<FOREIGNKEYNAME>','otherKey' => '<OTHERKEYNAME>') ,
//        'hasmany' => array(self::HAS_MANY, Area::class, 'table' => '<TABLENAME>','foreignKey' => '<FOREIGNKEYNAME>'),
    ];

    public static $rules = [
//        'username' => 'required|between:4,255|unique:users,username',
    ];

    public $columnsForSelectList = ['nome'];
     //['id','nome'];

    public $defaultOrderColumns = ['nome' => 'ASC', ];
     //['cognome' => 'ASC','nome' => 'ASC'];

    public $columnsSearchAutoComplete = ['nome'];
     //['cognome','denominazione','codicefiscale','partitaiva'];

    public $nItemsAutoComplete = 20;
    public $nItemsForSelectList = 100;
    public $itemNoneForSelectList = false;
    public $fieldsSeparator = ' - ';

    public function setFieldsFromResource($inputArray = array(),$field = 'resource') {

        $resource = json_decode(Arr::get($inputArray,$field,[]),true);

        $resourceId = Arr::get($resource,'id',false);

        $this->ext = pathinfo($resourceId, PATHINFO_EXTENSION);
        $this->filename = Arr::get($resource,'filename','nofilename');

    }


    public function getUrl()
    {
        return 'downloadmediable/importazione/'.$this->getKey();
    }
    public function getUrlAttribute()
    {
        if ($this->filename == 'nofilename') {
            return '';
        }
        $url = $this->getUrl();
        return '/' . $url;
    }

    protected function getDataAttribute($value) {
        $value = $this->fromJson($value);
        if (!$value) {
            return [
                'sheets' => [],
            ];
        }
        return $value;
    }

    public function getAbsoluteStorageFilename() {
        return $this->getStorageFilename();
    }
}
