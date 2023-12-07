<?php
namespace app\ui\contextMenu;

use app;

class DirectoryContextMenu extends FileContextMenu
{
    protected function setItems () {
        $this->helper->addItem(Localization::get('ui.contextMenu.showInExplorer'), [
            ContextMenuEvents::getInstance(app()->form("MainForm")), 'showInExplorer'],
            $this->helper->makeIcon($this->iconPath . 'open-folder.png')
        );
    }
}