<?php

namespace Modules\CupChart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImportazioneTabella;
use App\Services\ChartData;
use App\Services\ElasticSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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


    public function export(Request $request) {
        try {
            $id = $request->get('id');
            $importazioneTabella = ImportazioneTabella::find($id);
            $folder = $id . "_" . date('Y-m-d');
            array_map('unlink', glob(storage_path($folder."/*.*") ));
            @rmdir(storage_path($folder));
            //@unlink(storage_path($folder));
            @mkdir(storage_path($folder),0777,true);
            $imgData = $request->get('imgData');
            //var_dump($imgData);
            $tmp = $request->get('chartData');
            $chartData = [];
            for ($i=0;$i<count($tmp);$i++) {
                $chartData[] = json_decode($tmp[$i],true);
            }
            for ($i=0;$i<count($imgData);$i++)  {
                $data = $imgData[$i];
                list($type, $data) = explode(';', $data);
                list(, $data)      = explode(',', $data);
                $data = base64_decode($data);
                $filename = str_replace(':','-',$chartData[$i]['type']);
                file_put_contents(storage_path($folder . "/" . $filename . "$i.png"), $data);
            }
            // creare excel
            $excelData = $importazioneTabella->getChartData();
            $this->_saveExcel($excelData,$folder,"dati_$id");
            $legends = json_decode($request->get('legends',"{}"),true);
            $this->_saveLegend($legends,$folder);
            return $this->_zipFile($id,$folder);
        } catch (\Exception $e) {
            return $e->getMessage() . ":" . $e->getLine();
        }
    }

    protected function _saveLegend($legends,$folder) {
        foreach ($legends as $key => $legend) {
            if (count($legend) > 0) {
                $testo = [];
                for($i=0;$i<count($legend);$i++) {
                    if ($i==0) {
                        $testo[] = "minore di " . number_format($legend[$i+1]['value'],2);
                    }
                    if ($i> 0 && $i < count($legend)-1) {
                        $testo[] = "da " . number_format($legend[$i]['value'],2) .
                                " a " . number_format($legend[$i+1]['value'],2);
                    }
                    if ($i == count($legend)-1) {
                        $testo[] = "maggiore di " . number_format($legend[$i]['value'],2);
                    }
                }
                $image = imagecreate(500, 300);

// Set the background color of image
                $background_color = imagecolorallocate($image, 255, 255, 255);

// Set the text color of image
                $text_color = imagecolorallocate($image, 0, 0, 0);

// Function to create image which contains string.
                $x = 10;
                $y = 10;
                $colorIndex = 0;
                foreach ($testo as $riga) {
                    list($r, $g, $b) = sscanf($legend[$colorIndex]['color'], "#%02x%02x%02x");
                    $color = imagecolorallocate($image, $r, $g, $b);
                    imagefilledrectangle($image,0 , $y, $x,$y+10,$color);
                    imagestring($image, 5, $x+5, $y,  $riga, $text_color);
                    $y+=20;
                    $colorIndex++;
                }

                imagepng($image,storage_path($folder . "/legenda_" . $key . ".png") );
                imagedestroy($image);
            }
        }
    }

    protected function _saveExcel($data,$folder,$name) {
        $spreadsheet = new Spreadsheet();  /*----Spreadsheet object-----*/
        $Excel_writer = new Xlsx($spreadsheet);  /*----- Excel (Xls) Object*/
        $activeSheet = $spreadsheet->getActiveSheet();
        $activeSheet->setTitle($name . "_0");

        $this->_saveImageinExcel($spreadsheet);

        $chart = new ChartData($data);
        $chartData = $chart->getData('table',[]);

        $activeSheet->setCellValue("A6",$chartData['description']);


        $startRow = 10;
        $startCol = "A";

        // lascio un numero di colonne pari al count delle serie left
        $leftSeries = $chartData['leftSeries'];
        $i = 0;
        foreach ($leftSeries as $key => $serie) {
            $col = chr(ord($startCol)+$i);
            $activeSheet->setCellValue($col.$startRow,$key);
            $i++;
        }

//        for ($i=0;$i<count($chartData['leftSeries']);$i++) {
//            $col = chr(ord($startCol)+$i);
//            $activeSheet->setCellValue($col.$startRow,'');
//        }
        // scrivo le intestazioni delle colonne
        $startCol = chr(ord($startCol)+count($chartData['leftSeries']));
        $values = $chartData['values'];
        $colKeys = array_keys($values);
        for($i=0;$i<count($colKeys);$i++) {
            $col = chr(ord($startCol)+$i);
            $activeSheet->setCellValue($col.$startRow,$colKeys[$i]);
        }

        $primaVolta = true;
        $startRow++;
        foreach ($values as $key => $value) {
            // scrivo i valori delle righe solo la prima volta
            if ($primaVolta) {
                $row = $startRow;
                $localCol = 'A';
                foreach ($value as $rowKey => $rowData) {
                    $tmp = explode(config('cupparis-chart.separatore_left'),$rowKey);
                    for ($i=0;$i<count($tmp);$i++) {
                        $col = chr(ord($localCol)+$i);
                        $activeSheet->setCellValue($col.$row,$tmp[$i]);
                    }
                    $row++;
                }
                $primaVolta = false;
            }
            // stampo i valori della colonna
            $row = $startRow;
            foreach ($value as $rowKey => $rowData) {
                $colVal = $rowData['total'];
                $activeSheet->setCellValue($startCol.$row,$colVal);
                $row++;
            }
            $startCol =  chr(ord($startCol)+1);
        }
        $filename = storage_path($folder . "/" . $name . "_$i.xlsx");
        $Excel_writer->save($filename);
        $options = [
            'filename' => $filename
        ];
        return $this->_outExcel($options,$Excel_writer);
    }

    protected function _zipFile($name,$folder) {
        $zip_file = $name . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $path = storage_path($folder);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($files as $name => $file)
        {
            // We're skipping all subfolders
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();

                // extracting filename with substr/strlen
                $relativePath = $folder . '/' . substr($filePath, strlen($path) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return response()->download($zip_file);
    }
    public function saveChart(Request $request) {
        //echo $request->get('name');
        $img = substr($request->get('imgdata'),22);
        //echo $img;
        //echo base64_decode($request->get('imgdata'));
        //echo $request->get('_token');
        $mimetype = "image/png";
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false); // required for some browsers
        header('Content-Type: '.$mimetype);
        header('Content-Disposition: attachment; filename="'.$request->get('name').'";');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.strlen($img));

        ob_clean();
        flush();
        return  base64_decode($img);

    }

//    public function saveExcel(Request $request) {
//        //echo $request->get('name');
//        $json = json_decode($request->get('json_data'),1);
//        $options = [
//            'filename' => $request->get('name','chart-data')
//        ];
//
//        $spreadsheet = new Spreadsheet();  /*----Spreadsheet object-----*/
//        $Excel_writer = new Xls($spreadsheet);  /*----- Excel (Xls) Object*/
//        $spreadsheet->setActiveSheetIndex(0);
//        $activeSheet = $spreadsheet->getActiveSheet();
//        $activeSheet->setTitle('Dati');
//
//        $row = 1;
//
//        if (array_key_exists('title',$options) )
//            $activeSheet->setCellValue("A$row",$options['title']);
//        else
//            $activeSheet->setCellValue("A$row",'titolo');
//        if (array_key_exists('sub_title',$options)) {
//            $row = 2;
//            $activeSheet->setCellValue("A$row",$options['sub_title']);
//        }
//        $row +=2;
//        $cols = range('A','Z');
////        print_r($json);
////        return ;
//
//        $this->_outExcel($options,$Excel_writer);
//        return ;
//
//        if (count($json['result']) == 0) {
//            $activeSheet->setCellValue('A1','Nessun dato presente');
//            $this->_outExcel($options,$Excel_writer);
//            return ;
//        }
//
//
//        $keys = array_keys($json['result'][0]);
//        foreach ($keys as $index => $key) {
//            $activeSheet->setCellValue($cols[$index]."$row" , $key);
//        }
//        $row++;
//        //print_r($data);
//        foreach ($json['result'] as $indexRow => $values) {
//            foreach ($keys as $indexCol => $key) {
//                //echo $indexRow.$key."\n";
//                //echo $data[$indexRow][$key] . "<br>";
//                $activeSheet->setCellValue($cols[$indexCol].($indexRow+$row) , $json['result'][$indexRow][$key]);
//            }
//        }
//        $row += count($json['result']) + 2;
//        if (array_key_exists('note',$options) ){
//            $activeSheet->setCellValue("A$row" , $options['note']);
//        }
//
//        //return ;
//        // --- filtri estrazione dati ---
//
//        $objWorkSheet = $spreadsheet->createSheet(1);
//        $objWorkSheet->setTitle('Parametri');
//        $spreadsheet->setActiveSheetIndex(1);
//        $activeSheet = $spreadsheet->getActiveSheet();
//        $row = 1;
//        $activeSheet->setCellValue('A' . $row,'Tabella e filtri attivi');
//
//        $row++;
//
//        foreach ($options['params'] as $key => $value) {
//            $activeSheet->setCellValue('A' . $row,$key);
//            $activeSheet->setCellValue('B' . $row,$value);
//            $row++;
//        }
//        $spreadsheet->setActiveSheetIndex(0);
//        //$activeSheet->setCellValue('A1' , 'New file content')->getStyle('A1')->getFont()->setBold(true);
//        $this->_outExcel($options,$Excel_writer);
//
//    }

    protected function _saveImageinExcel($spreadsheet,$row,$col,$imgAttrs) {
        // sovrascrivere questo metodo per eventuali immagini da salvare nell'execel sotto un esempio

//        $pr_file = "filename";
//        $objDrawing = new PHPExcel_Worksheet_Drawing();
//        $objDrawing->setName('Logo');
//        $objDrawing->setDescription('Logo');
//        $logo = base_url() . 'images/logo.png'; // Provide path to your logo file
//        $objDrawing->setPath($pr_file);
//        $objDrawing->setOffsetX(8);    // setOffsetX works properly
//        $objDrawing->setOffsetY(300);  //setOffsetY has no effect
//        $objDrawing->setCoordinates('B1');
//        $objDrawing->setHeight(75); // logo height
//        $objDrawing->setWorksheet($this->excel->getActiveSheet());

    }

    protected function _outExcel($options,$Excel_writer) {
        $filename = array_key_exists('filename',$options)?$options['filename']:'chart-data';
        $filename = $filename?$filename:$options['filename'];
        //die("filename $filename" . "\n");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'. $filename .'.xls"'); /*-- $filename is  xsl filename ---*/
        header('Cache-Control: max-age=0');
        $Excel_writer->save('php://output');
    }

    protected function _error($msg)
    {
        $this->json['error'] = 1;
        $this->json['msg'] = $msg;
    }

    protected function _json()
    {
        return Response::json($this->json);
    }

}
