<?php
namespace app\events;

use framework;
use app;
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
            $result = app()->form("MainForm")->ini->get('directoryList');
            
            if (is_array($result)) {
                $result[] = $path;
            } else {
                if ($result != null) {
                    $result = [$result, $path];
                } else {
                    $result = $path;
                }
            }
            
            app()->form("MainForm")->ini->set('directoryList', $result);
            app()->form("MainForm")->combobox->items->add([$path, 'res://.data/img/ui/folder-60.png']);
            app()->form("MainForm")->combobox->selectedIndex = app()->form("MainForm")->combobox->items->count() - 1;
            
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
                $menuItem->graphic->selected = true;
                
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
    
    public function about () {
        if (!($this->overlayContainer instanceof OverlayContainer)) {
            $this->overlayContainer = new OverlayContainer();
            $this->overlayContainer->addContent(new AboutContainer());
            app()->form("MainForm")->add($this->overlayContainer->getNode());
        }
        
        $this->overlayContainer->show();
    }
}