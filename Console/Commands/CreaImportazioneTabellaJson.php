<?php namespace Modules\CupChart\Console\Commands;


use App\Models\Importazione;
use App\Models\ImportazioneTabella;
use App\Services\Importazione\ElasticJsonService;
use App\Services\Importazione\SplitTablesService;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class CreaImportazioneTabellaJson extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crea-importazione-tabella-json
                    {id : Id della importazione_tabella da lavorare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea CNR-GAP json x elastic da xls file import (singola tabella).';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    protected $importazioneService;

    protected $elasticPath = 'files/elastic/';

    /**
     * Create a new reminder table command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;


    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $tabellaId = $this->argument('id');

        $tabella = ImportazioneTabella::find($tabellaId);
        if (!$tabella) {
            throw new \Exception("Tabella importazione inesistente");
        }

        $importazione = $tabella->importazione;

        if (!$importazione) {
            throw new \Exception("Importazione inesistente");
        }

        try {

            $this->importazioneService = new ElasticJsonService($importazione);


            $dir = storage_path($this->elasticPath . $importazione->getKey());
            if (!File::exists($dir)) {
                File::makeDirectory($dir);
            }
            $lastSheetCode = null;
            $prog = 1;

                $json = $this->importazioneService->getTableJson($tabella->sheetname, $tabella->nome,
                    $tabella->metadata);
                $sheetCode = substr(str_pad(str_replace(' ', '', $tabella->sheetname), 3, 'X', STR_PAD_RIGHT), 0, 3);
                if ($sheetCode == $lastSheetCode) {
                    $prog++;
                } else {
                    $prog = 1;
                }
                $elId = [
                    $tabella->importazione_id,
                    $tabella->sheetname,
                    $tabella->progressivo,
                ];

                //$filename = $dir . '/' . 'values_' . $sheetCode . $prog . '_' . $tabella->progressivo . '.json';
                $filename = $dir .'/' . implode('_',$elId) . ".json";
                File::put($filename, $json);

        } catch (\Exception $e) {
            throw $e;
        }


        $this->comment('Elastic json per importazione tabella ' . $tabellaId . ' creato');

    }


}
