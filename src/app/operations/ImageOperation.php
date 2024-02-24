<?php
namespace app\operations;

use framework;
use gui;
use app;

class ImageOperation extends AbstractOperation
{
    public function getActiveTab ()
    {
        return self::VIEW;
    }
    
    public function forExt ()
    {
        return "image";
    }
    
    public function action ()
    {
        app()->form("MainForm")->showCodeInBrowser("Binary data");
        app()->form("MainForm")->image->image = new UXImage($this->output);
    }
}