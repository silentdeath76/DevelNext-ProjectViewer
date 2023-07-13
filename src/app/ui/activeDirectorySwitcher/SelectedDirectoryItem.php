<?php
namespace app\ui\activeDirectorySwitcher;

use gui;
use app;

class SelectedDirectoryItem extends DirectoryItem
{
    
    /**
     * @var UXLabelEx
     */
    private $title;
    
    
    protected function make () {
        parent::make();
        
        $this->text->font->bold = true;
        
        $this->title = new UXLabelEx("");
        $this->title->autoSize = true;
        $this->title->textColor = '#000000';
        
        $this->itemsContainer->children->insert(0, $this->title);
    }
    
    public function setTitle ($text) {
        $this->title->text = $text;
    }
}