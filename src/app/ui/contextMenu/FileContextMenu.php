<?php
namespace app\ui\contextMenu;

use std;
use app;
use gui;

class FileContextMenu extends AbstractNode
{
    protected $iconPath = 'res://.data/img/context-menu-icons/';
    /**
     * @var UXContextMenu
     */
    protected $container;
    
    protected $helper;
    
    protected function make () {
        $this->container = new UXContextMenu();
        $config = new Configuration();
        $config->set(ContextMenuHelper::GRAPHIC_WIDTH, 16);
        $config->set(ContextMenuHelper::GRAPHIC_HEIGHT, 16);
        
        $this->helper = ContextMenuHelper::of($this->container, $config);
        $this->setItems();
    }
    
    public function showByNode ($node) {
        $this->container->showByNode($node->sender, $node->x, $node->y);
    }
    
    protected function setItems () {
        $this->helper->addItem(Localization::get('ui.contextMenu.saveAs'), 
            [ContextMenuEvents::getInstance(app()->form("MainForm")), "saveAs"],
            $this->helper->makeIcon($this->iconPath . 'save.png')
        );
        $this->helper->addItem(Localization::get('ui.contextMenu.rename'),
            [ContextMenuEvents::getInstance(app()->form("MainForm")), "rename"],
            $this->helper->makeIcon($this->iconPath . 'edit.png')
        );
        $this->helper->addItem(Localization::get('ui.contextMenu.delete'),
            [ContextMenuEvents::getInstance(app()->form("MainForm")), "delete"],
            $this->helper->makeIcon($this->iconPath . 'delete.png')
        );
    }
}