<?php
namespace app\operations;

abstract class AbstractOperation 
{
    const CODE = 0;
    const VIEW = 1;
    
    protected $output;
    
    public function getActiveTab ()
    {
        return self::CODE;
    }
    
    public function setOutput ($output)
    {
        $this->output = $output;
    }
    
    abstract public function forExt ();
    
    abstract public function action ();
}