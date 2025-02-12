<?php
namespace app\operations;

use std;
use app;

trait OperationTrait 
{
    private $operationList = [];
    
    public function registerOperation ($class)
    {
        $c = new $class();
        if (!($c instanceof AbstractOperation)) {
            throw new IllegalArgumentException("Class {$c} must implement AbstractOperation.");
        }
        
        $this->operationList[$class] = $c;
    }
    
    public function findOperation ($zipPath, $output, $ext)
    {
        /** @var AbstractOperation $operation */
        foreach ($this->operationList as $operation) {
            if (is_array($operation->forExt()) && in_array(fs::ext($zipPath), $operation->forExt())) {
                return $this->triggerOperation ($operation, $output, $ext);
            } else if (fs::ext($zipPath) == $operation->forExt()) {
                return $this->triggerOperation ($operation, $output, $ext);
            } else if ($operation->forExt() === $ext) {
                return $this->triggerOperation ($operation, $output, $ext);
            }
        }
        
        return false;
    }
    
    public function triggerOperation ($operation, $output, $ext)
    {
        $operation->setOutput($output);
        $operation->action($ext);
        $this->tabPane->selectedIndex = $operation->getActiveTab();
    }
}