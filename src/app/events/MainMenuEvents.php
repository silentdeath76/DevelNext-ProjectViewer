<?php
namespace app\events;

use std;
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
    
    public function changeTheme ($ev, $menu, $themeList) {
        foreach ($menu->items as $menuItem) {
            $name = array_search($themeList, $menuItem->graphic->text, false);
            
            if ($ev->sender->graphic === $menuItem->graphic) {
                app()->form("MainForm")->ini->set('theme', $name);
                app()->form("MainForm")->data('theme', $name);
                
                try {
                    app()->form("MainForm")->browser->engine->userStyleSheetLocation = new ResourceStream('/.data/web/' . $name . '.css')->toExternalForm();
                } catch (Exception $ex) {
                    app()->form("MainForm")->errorAlert($ex);
                }
                
                $menuItem->graphic->enabled = false;
                
                app()->form("MainForm")->addStylesheet('.theme/' . $name . '.theme.fx.css');
                
                continue;
            }
            
            $menuItem->graphic->enabled = true;
            $menuItem->graphic->selected = false;
            
            if (app()->form("MainForm")->hasStylesheet('.theme/' . $name . '.theme.fx.css')) {
                app()->form("MainForm")->removeStylesheet('.theme/' . $name . '.theme.fx.css');
            }
        }
    }
}