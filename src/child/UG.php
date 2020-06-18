<?php

namespace BusyPHP\helper\child;

class UG
{
    public $iForm;
    
    public $bForm;
    
    public $mForm;
    
    public $eForm;
    
    public $bType;
    
    
    public function __construct($i, $b, $m, $e, $bt)
    {
        $this->iForm = $i;
        $this->bForm = $b;
        $this->mForm = $m;
        $this->eForm = $e;
        $this->bType = $bt;
    }
}
