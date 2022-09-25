<?php
namespace app\events;

use Exception;
use gui;

class MainMenuEvents 
{
    public function selectedFolder () {
        $dc = new UXDirectoryChooser();
            
        if (($path = $dc->showDialog(app()->form("MainForm"))) == null) return;
            
        app()->form("MainForm")->projectDir = $path;
            
        try {
            app()->form("MainForm")->ini->set('ProjectDirectory', $path);
        } catch (Exception $ex) {
            app()->form("MainForm")->errorAlert($ex);
        }
            
        app()->form("MainForm")->tree->root->children->clear();
            
        try {
            app()->form("MainForm")->fsTree->setDirectory($path);
        } catch (Exception $ex) {
            app()->form("MainForm")->errorAlert($ex);
        }
    }
    
    public function changeTheme () {
        
    }
}