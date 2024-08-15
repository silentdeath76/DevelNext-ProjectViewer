<?php
namespace app\operations;

use std;
use app;

trait OpertaionTrait 
{
    private $operationList = [];
    
    public function registerOperation ($class)
    {
        $this->operationList[$class] = new $class();
    }
    
    private function findOperation ($zipPath, $output, $ext)
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
    
    private function triggerOperation ($operation, $output, $ext)
    {
        $operation->setOutput($output);
        $operation->action($ext);
        $this->tabPane->selectedIndex = $operation->getActiveTab();
    }
}