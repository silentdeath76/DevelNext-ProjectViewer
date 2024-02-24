<?php
namespace app\operations;

use framework;
use gui;
use std;
use app;

class FXMLOperation extends AbstractOperation
{
    public function getActiveTab ()
    {
        return self::VIEW;
    }
    
    public function action ()
    {
        $temp = (string) $this->output;
        
        app()->form("MainForm")->showCodeInBrowser($temp, 'xml');
        $this->_showForm($temp, app()->form("MainForm")->image);
    }
    
    public function forExt ()
    {
        return 'fxml';
    }
    
    private function _showForm ($formData, $outputImage) {
        $n = new Environment();
        $n->importAutoLoaders();
        
        $n->importClass(MainForm::class);
        $n->importClass(MainModule::class);
        $n->importClass(FXMLOperation::class);
        
        $n->execute(function () use ($formData, $outputImage) {
            $layout = new UXLoader()->loadFromString($formData);
            $form = new UXForm();
            // $form->show();
            $form->add($layout);
            $outputImage->image = $form->layout->snapshot();
        });
    }
}