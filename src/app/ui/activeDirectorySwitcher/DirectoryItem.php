<?php
namespace app\ui\activeDirectorySwitcher;

use app;
use app\ui\AbstractNode;
use gui;

class DirectoryItem extends AbstractNode
{
    /**
     * @var UXImageArea
     */
    protected $icon;
    
    /**
     * @var UXLabelEx
     */
    protected $text;
    
    
    /**
     * @var UXVBox
     */
    protected $itemsContainer;
    
    protected function make () {
        $this->container = new UXHBox();
        $this->container->alignment = 'CENTER_LEFT';
        $this->container->spacing = 10;
        $this->container->padding = 5;
        $this->container->css("-fx-font-family: 'Open Sans';");
        
        $this->icon = new UXHBox();
        $this->icon->minWidth = 11;
        $this->icon->minHeight = $this->icon->maxHeight = 8;
        
        $this->container->add($this->icon);
        
        $this->itemsContainer = new UXVBox();
        
        $this->text = new UXLabelEx("");
        $this->text->autoSize = true;
        $this->text->textColor = '#000000';
        
        $this->itemsContainer->add($this->text);
        
        $this->container->add($this->itemsContainer);
    }
    
    public function setImage ($image) {
        $this->icon->classes->clear();
        $this->icon->classes->add($image);
    }
    
    public function setText ($text, $width = 0) {
        $this->text->text = $text;
    }
}