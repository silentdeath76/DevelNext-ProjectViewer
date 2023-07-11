<?php
namespace app\ui;

use gui;
use app;

class IconFileSelected extends AbstractNode
{
    /**
     * @var UXHBox
     */
    private $svg;
    
    /**
     * @var UXLabelEx
     */
    private $label;
    
    
    protected function make () {
        $this->container = new UXHBox();
        $this->container->leftAnchor = 0;
        $this->container->rightAnchor = 0;
        $this->container->paddingTop = 15;
        $this->container->alignment = 'CENTER';
        
        $this->svg = new UXHBox();
        $this->svg->alignment = 'CENTER';
        $this->svg->minWidth = 52;
        $this->svg->minHeight = 68;
        $this->svg->classes->add('file-icon');
        
        $this->label = new UXLabelEx("");
        $this->label->autoSize = true;
        $this->label->font->size = 18;
        
        $this->svg->add($this->label);
        
        $this->container->add($this->svg);
    }
    
    public function updateClasses (array $classes) {
        $this->svg->classes->clear();
        $this->svg->classes->addAll($classes);
    }
    
    public function updateText ($text) {
        if ($this->label->text == $text) return;
        $this->label->text = $text;
    }
    
    public function setSize ($width, $height, $size = 0) {
        $this->svg->minWidth = $width;
        $this->svg->minHeight = $height;
        
        if ($size === 0) return;
        
        $this->label->font->size = $size;
    }
    
    public function clear () {
        $this->container->paddingTop = 0;
        $this->container->alignment = 'TOP_LEFT';
        $this->container->leftAnchor = null;
        $this->container->right = null;
    }
}