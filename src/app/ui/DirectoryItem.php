<?php
namespace app\ui;

use app;
use gui;

class DirectoryItem extends AbstractNode
{
    /**
     * @var UXImageArea
     */
    private $icon;
    
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
        $this->container->css("-fx-font-family: 'Arial';");
        
        $this->icon = new UXImageArea(new UXImage('res://.data/img/ui/folder-60.png', 18, 18));
        $this->icon->proportional = true;
        $this->icon->width = 18;
        $this->icon->height = 18;
        
        $this->container->add($this->icon);
        
        $this->itemsContainer = new UXVBox();
        
        $this->text = new UXLabelEx("");
        $this->text->autoSize = true;
        $this->text->textColor = '#000000';
        
        $this->itemsContainer->add($this->text);
        
        $this->container->add($this->itemsContainer);
    }
    
    public function setImage (UXImage $image) {
        $this->icon->image = $image;
    }
    
    public function setText ($text, $width = 0) {
        $this->text->text = $text;
    }
}