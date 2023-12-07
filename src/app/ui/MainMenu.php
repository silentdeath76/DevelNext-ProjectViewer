<?php
namespace app\ui;

use gui;
use app;

class MainMenu extends AbstractNode
{
    /**
     * @var UXMenuBar
     */
    protected $container;
    
    protected function make () {
        $mainMenuEvents = new MainMenuEvents();
        $this->container = new UXMenuBar();
        $this->container->leftAnchor = $this->container->rightAnchor = 0;
        
        ContextMenuHelper::of($this->container)->addCategory(Localization::get('ui.mainMenu.selectDirectory'), [$mainMenuEvents, 'selectedFolder']);
        
        $themeCategory = ContextMenuHelper::of($this->container)->addCategory(Localization::get('ui.mainMenu.theme'));
        
        $themeList = [
            "light" => Localization::get('ui.mainMenu.theme.light'),
            "dark" => Localization::get('ui.mainMenu.theme.dark'),
            "nord" => Localization::get('ui.mainMenu.theme.nord')
        ];
        
        foreach ($themeList as $theme => $text) {
            $themeCategory->addItem(null, function ($ev) use ($themeCategory, $themeList, $mainMenuEvents) {
                $mainMenuEvents->changeTheme($ev, $themeCategory->getTarget(), $themeList);
            }, $node = new UXCheckbox($text));
            
            if ($theme === app()->form("MainForm")->data('theme')) {
                $node->selected = true;
                $node->enabled = false;
                app()->form("MainForm")->addStylesheet('.theme/' . $theme. '.theme.fx.css');
            }
        }
        
        ContextMenuHelper::of($this->container)->addCategory(Localization::get('ui.mainMenu.about'), [$mainMenuEvents, 'about']);
    }
}