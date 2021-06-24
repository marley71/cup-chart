<?php

namespace Modules\CupSite\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ChartData;
use App\Services\ElasticSearch;
use Illuminate\Support\Arr;

class ChartController extends Controller
{
    protected $json = [
        'error' => 0,
        'msg' => '',
    ];

    public function getDistribuzione($id,$cupType) {

        $importazioneID = 'demo';

        try {
            if (env('USE_ELASTIC')) {
                $es = new ElasticSearch();
                $data = $es->get([
                    'index' => env('ELASTIC_INDEX'),
                    'id' => $id
                ]);
                $data = $data['_source'];
            } else {
                $idParts = explode('_',$id);
                $filename = storage_path('files/elastic/'.$idParts[0].'/'.$id.".json");
                $data = json_decode(file_get_contents($filename),true);
            }

            //$data = json_decode(file_get_contents(storage_path('files/elastic/'.$importazioneID.'/'.$id.".json")),true);

            //print_r($data);
            $chartData = new ChartData($data);
            $params = [
                'filters' => request()->input('filters',[]),
                'series' => \request()->input('series',[])
            ];
            $data = $chartData->getData($cupType,$params);
            $this->json['result'] = $data;
            $this->json['chart_id'] = $id;
            return $this->_json();

        } catch (\Exception $e) {
            $this->_error($e->getMessage());
            return $this->_json();
        }

    }

    /**
     * ritorna la lista di un indice elastic
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getList() {
        $es = new ElasticSearch();
        $data = $es->search([
            'index' => env('ELASTIC_INDEX','prova'),
            'size' => 50,
        ]);
        $this->json['result'] = [
            'data' => []
        ];
        foreach ($data['hits']['hits'] as $source) {
            $s=[];
            $s['id'] = $source['_id'];
            $s['sheetname'] = $source['_source']['sheetname'];
            $s['titolo'] = $source['_source']['titolo'];
            $s['series'] = $source['_source']['series'];
            $this->json['result']['data'][] = $s;

        }

        return $this->_json();

    }

    /**
     * ritorna i dati di un xls importato in formato json
     * @param $elasticId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItem($elasticId) {
        $this->json['result'] = [];
        if (env('USE_ELASTIC')) {
            $es = new ElasticSearch();
            $data = $es->get([
                'index' => env('ELASTIC_INDEX'),
                'id' => $elasticId
            ]);
            $data = $data['_source'];
            $source = Arr::get($data,'_source',[]);
            if (count(array_keys($source)) > 0) {
                $s['id'] = $data['_id'];
                $s['sheetname'] = $source['sheetname'];
                $s['titolo'] = $source['titolo'];
                $s['series'] = $source['series'];
                $this->json['result'] = $s;
            } else {
                $this->_error('documento non trovato');
            }
        } else {
            $idParts = explode('_',$elasticId);
            $filename = storage_path('files/elastic/'.$idParts[0].'/'.$elasticId.".json");
            $this->json['result'] = json_decode(file_get_contents($filename),true);
        }

        return $this->_json();
    }
}
