<?php

namespace App\Services\Importazione;

/*
 * CLASSE PER GESTIRE LA FASE DI UPLOADI UN FILE.
 * EVENTUALMENTE DA FARE COME PROVIDER E FACADE IN FUTURO.
 * PER ORA SERVIZIO AL VOLO COME SINGLETON.
 */

use App\Models\Importazione;
use App\Models\ImportazioneTabella;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RenderTableService
{

    protected $importazioneTabella;
    protected $importazione;
    protected $objectReader;
    protected $dataFile;

    protected $sheet;

    public function __construct(ImportazioneTabella $importazioneTabella)
    {
        $this->importazioneTabella = $importazioneTabella;
        $this->importazione = $importazioneTabella->importazione;
        if (!$this->importazione->fileExists()) {
            throw new \Exception("File di origine non trovato.");
        }
        $this->dataFile = storage_path($this->importazione->getAbsoluteStorageFilename());

        $inputFileType = IOFactory::identify($this->dataFile);
        $this->objectReader = IOFactory::createReader($inputFileType);

        try {
            //Carico il foglio indicato nella cofnigurazione
            //Se non presente carico il foglio 0;
            $this->objectReader->setLoadSheetsOnly($importazioneTabella->sheetname);

            $spreadSheet = $this->objectReader->load($this->dataFile);
            $spreadSheet->setActiveSheetIndexByName($importazioneTabella->sheetname);

            $this->sheet = $spreadSheet->getActiveSheet();
        } catch (\Exception $e) {
            $msg = 'Problemi ad aprire il file: non sembra un file salvato correttamente come file excel. Provare ad aprirlo con Excel e salvarlo nuovamente.<br/>';
            $msg .= $e->getMessage();
            throw new \Exception($msg);
        }
    }

    public function getHtmlFromMetadata()
    {

        $title = "Tabella n. " . $this->importazioneTabella->progressivo . ' - ' . $this->importazioneTabella->nome;
        $metadata = $this->importazioneTabella->metadata;
        $init = Arr::get($metadata, 'init', 'A1');
        $end = Arr::get($metadata, 'end', 'A1');
        $initData = Arr::get($metadata, 'initData', 'A1');

        list($initColumn, $initRow) = Coordinate::coordinateFromString($init);
        list($endColumn, $endRow) = Coordinate::coordinateFromString($end);
        list($initDataColumn, $initDataRow) = Coordinate::coordinateFromString($initData);
        $initColumnIndex = Coordinate::columnIndexFromString($initColumn);
        $endColumnIndex = Coordinate::columnIndexFromString($endColumn);
        $initDataColumnIndex = Coordinate::columnIndexFromString($initDataColumn);


        $columns = [];
        for ($cIndex = $initColumnIndex; $cIndex <= $endColumnIndex; $cIndex++) {
            $c = Coordinate::stringFromColumnIndex($cIndex);
            $columns[$cIndex] = $c;
        }
        $rows = range($initRow, $endRow);

        $headers = Arr::get($metadata, 'headers', []);


        $topHeaders = [];
        foreach (Arr::get($headers, 'corner', []) as $rowKey => $headerRow) {
            foreach ($headerRow as $columnKey => $headerRowColumn) {

                $cleanedMetadata = $this->cleanCellSpans($headerRowColumn,true);
                if (!is_null($cleanedMetadata)) {
                    $topHeaders[$rowKey][$columnKey] = $cleanedMetadata;
                }
            }
        }
        foreach (Arr::get($headers, 'top', []) as $rowKey => $headerRow) {
            foreach ($headerRow as $columnKey => $headerRowColumn) {

                $cleanedMetadata = $this->cleanCellSpans($headerRowColumn);
                if (!is_null($cleanedMetadata)) {
                    $topHeaders[$rowKey][$columnKey] = $cleanedMetadata;
                }
            }
        }

        $leftHeaders = [];
        foreach (Arr::get($headers, 'left', []) as $rowKey => $headerRow) {
            foreach ($headerRow as $columnKey => $headerRowColumn) {

                $cleanedMetadata = $this->cleanCellSpans($headerRowColumn);
                if (!is_null($cleanedMetadata)) {
                    $leftHeaders[$rowKey][$columnKey] = $cleanedMetadata;
                }
            }
        }

        /*
         * PRENDO I DATI
         */

        $datatable = [];

        for ($row = $initDataRow; $row <= $endRow; $row++ ) {

            for ($columnIndex = $initDataColumnIndex; $columnIndex <= $endColumnIndex; $columnIndex++ ) {

                $column = Coordinate::stringFromColumnIndex($columnIndex);
                $coordinate = $column . $row;
                $cell = $this->sheet->getCell($coordinate);
                $datatable[$row][$columnIndex] = $cell->getFormattedValue();
            }
        }

        $view = view('importazioni.tabella', compact('title', 'metadata'
            , 'columns', 'rows', 'topHeaders', 'leftHeaders', 'datatable'
        ));

        $html = $view->toHtml();
        return $html;

    }

    protected function cleanCellSpans($cellData,$isCorner = false)
    {
        if (!array_key_exists('colspan', $cellData)) {
            return null;
        }

        if ($cellData['colspan'] == 1) {
            unset($cellData['colspan']);
        }
        if ($cellData['rowspan'] == 1) {
            unset($cellData['rowspan']);
        }
        if ($isCorner) {
            $cellData['corner'] = true;
        } else {
            $cellData['corner'] = false;
        }
        return $cellData;
    }
//
//    protected function getTableHeaders(Worksheet $sheet, $initColumn, $initRow, $headersColumns, $headersRows, $maxColumnIndex, $finalRow)
//    {
//
//        $initColumnIndex = Coordinate::columnIndexFromString($initColumn);
//        $initColumnIndexTop = $initColumnIndex + $headersColumns;
//
//        $mergeCellsFromSheet = $sheet->getMergeCells();
//
//        $mergeCells = [];
//        foreach ($mergeCellsFromSheet as $mergeCell) {
//            list($key, $value) = explode(':', $mergeCell);
//            $mergeCells[$key] = $value;
//        }
//
//        $headers = [];
//
//        //VERTICE
//        $cornerHeaders = [];
//        $cornerCoordinate = $initColumn.$initRow;
//        $cornerCell = $sheet->getCell($cornerCoordinate);
//        $this->setCellValues($cornerHeaders, $cornerCell);
//        $this->setCellSpans($cornerHeaders, $cornerCoordinate, $cornerCell, $initColumnIndex, $initRow, $mergeCells);
//
//        $topHeaders['corner'] = $cornerHeaders;
//
//
//        //PRIMA PASSATA SULLE RIGHE DI INTESTAZIONE SOPRA LA TABELLA
//        $topHeaders = [];
//        for ($row = $initRow; $row < ($initRow + $headersRows); $row++) {
//
//            $topHeaders[$row] = [];
//
//            for ($columnIndex = $initColumnIndexTop; $columnIndex <= $maxColumnIndex; $columnIndex++) {
//
//                $headerData = [];
//
//                $column = Coordinate::stringFromColumnIndex($columnIndex);
//
//                $coordinate = $column . $row;
//                $cell = $sheet->getCell($coordinate);
//
//                $this->setCellValues($headerData, $cell);
//                $this->setCellSpans($headerData, $coordinate, $cell, $columnIndex, $row, $mergeCells);
//
//                $topHeaders[$row][$columnIndex] = $headerData;
//
//            }
//
//        }
//
//        $headers['top'] = $topHeaders;
//
//
//        //SECONDA PASSATA SULLE COLONNE DI INTESTAZIONE A SINISTRA DELLA TABELLA
//        $leftHeaders = [];
//
//        $initRowLeft = $initRow + $headersRows;
//        for ($row = $initRowLeft; $row <= $finalRow; $row++) {
//            $leftHeaders[$row] = [];
//
//            for ($columnIndex = $initColumnIndex; $columnIndex < ($initColumnIndex + $headersColumns); $columnIndex++) {
//
//                $headerData = [];
//
//                $column = Coordinate::stringFromColumnIndex($columnIndex);
//
//                $coordinate = $column . $row;
//                $cell = $sheet->getCell($coordinate);
//
//                $this->setCellValues($headerData, $cell);
//                $this->setCellSpans($headerData, $coordinate, $cell, $columnIndex, $row, $mergeCells);
//
//                $leftHeaders[$row][$columnIndex] = $headerData;
//
//            }
//
//        }
//
//        $headers['left'] = $topHeaders;
//
//        return $headers;
//
//    }
//
//
//
//    protected function setCellValues(&$headerData, $cell)
//    {
//        $headerData['fVal'] = $cell->getFormattedValue();
//        $headerData['val'] = $cell->getvalue();
//    }
//
//    protected function setCellSpans(&$headerData, $coordinate, $cell, $columnIndex, $row, $mergeCells = [])
//    {
//        if (array_key_exists($coordinate, $mergeCells)) {
//            $finalCoordinate = $mergeCells[$coordinate];
//            list($finalMergeColumn, $finalMergeRow) = Coordinate::coordinateFromString($finalCoordinate);
//            $headerData['colspan'] = Coordinate::columnIndexFromString($finalMergeColumn) - $columnIndex + 1;
//            $headerData['rowspan'] = $finalMergeRow - $row + 1;
//
//        } elseif ($cell->isInMergeRange()) {
////                    continue;
//        } else {
//            $headerData['colspan'] = 1;
//            $headerData['rowspan'] = 1;
//        }
//    }
//


}
