<?php

namespace App\Foorm\Importazione\Actions;


use Gecche\Foorm\FoormAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SplitTables extends \Modules\CupChart\Foorm\Importazione\Actions\SplitTables
{

    protected $fieldToSet;
    protected $valueToSet;

    protected $validationSettings;


    public function performAction()
    {

        Artisan::call('manage-importazione',['id' => $this->model->getKey()]);

        $this->actionResult = [];

        return $this->actionResult;

    }

    public function validateAction()
    {
        return true;
    }


}
