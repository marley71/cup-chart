<?php

namespace Modules\CupChart\Services\Importazione;

use PhpOffice\PhpSpreadsheet\Cell\Cell;

trait SpreadsheetTrait {

    protected function getCalcValue(Cell $cell) {

        switch ($cell->getDataType()) {
            case 'f':
                return $cell->getOldCalculatedValue();
            default:
                return $cell->getCalculatedValue();
        }
    }

}
