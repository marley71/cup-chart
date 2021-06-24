<?php
/**
 * Created by PhpStorm.
 * User: pier
 * Date: 30/01/17
 * Time: 11:20
 */

namespace App\Services;


//use Elasticsearch\ClientBuilder;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ElasticSearch
{

    protected $client = null;

    public function __construct()
    {
        $this->client = $this->getClient();
    }


    public function store($index,$values,$type=null,$id=null) {
        $params = [
            'index' => $index,
            'type' => $type?$type:'_doc',
            //'id' => $id,
            'body' => $values
        ];
        if ($id)
            $params['id'] = $id;
        return $this->client->index($params);

    }

    public function bulk($index,$type,$values) {
        $params = [
            'index' => $index,
            //'type' => $type,
            //'id' => $id,
            'body' => []
        ];

        for ($i=0;$i<count($values);$i++) {
            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                ],
//                'type' => [
//                    '_type' => $type
//                ]
            ];

            $params['body'][] = $values[$i];
        }
//        echo "---bulk---\n";
//        print_r($params);
//        echo "------ fine bulk ---- \n";
        return $this->client->bulk($params);

    }


    public function get($params)
    {
        return $this->client->get($params);
    }

    public function update($index,$type,$id,$values) {
        $params = [];
        $params['type'] = $type;
        $params['index'] = $index;
        $params['id'] = $id;
        $params['body'] = [
            'doc' => $values
        ];
        $this->client->update($params);
    }

    public function updateByQuery($index,$type,$query) {
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => $query
            //'body' => ['query' => $query]
        ];
        return $this->client->updateByQuery($params);
    }

    public function getClient() {
        $hosts = ['http://' . env('DB_HOST_ELASTIC','localhost') . ":" . env('DB_PORT_ELASTIC','9200')];
        $builder = ClientBuilder::create()->setHosts($hosts)->build();
        return $builder;
    }

    protected function _getElasticParams() {
        return Config::get('insta_monitor3.elastic');
    }

    public function search($params) {
        try {
            return $this->client->search($params);
        } catch (\Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    public function exist($index) {
        $params = [
            'index' => $index
        ];
        return $this->client->indices()->exists($params);
    }

    public function delete($index) {
        $params = [
            'index' => $index
        ];
        return $this->client->indices()->delete($params);
    }

    public function create($index,$mapping = null) {
        $params = [
            'index' => $index,
            'body'  => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 0,
                    "index.refresh_interval" => "5s",
                    'index.mapping.total_fields.limit' => 7000,
                ]
            ]
        ];
        if ($mapping)
            $params['body']['mappings'] = $mapping;
        $response = $this->client->indices()->create($params);
    }

}
