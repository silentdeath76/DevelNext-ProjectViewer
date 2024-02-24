<?php
namespace app\operations;

class UnsupportedFilesOperation extends AbstractOperation
{
    public function getActiveTab ()
    {
        return self::CODE;
    }
    
    public function forExt ()
    {
        return ["exe", "dll", "jar", "ttf", "zip"];
    }
    
    public function action ($ext = null)
    {
        app()->form("MainForm")->showCodeInBrowser($this->output, $ext);
    }
}