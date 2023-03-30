<?php
namespace app\helpers;

use gui;
use std;
use app;

class ContextMenuHelper 
{
    const GRAPHIC_WIDTH = 'graphic.width';
    const GRAPHIC_HEIGHT = 'graphic.height';
    
    /**
     * @var UXContextMenu
     */
    private $target;
    
    /**
     * @var Configuration
     */
    private $config;
    
    /**
     * @return ContextMenuHelper
     */
    public static function of ($target, Configuration $config = null) {
        if (!($target instanceof UXContextMenu || $target instanceof UXMenu || $target instanceof UXMenuBar)) {
            throw new IllegalArgumentException('Args most be php\gui\UXContextMenu or php\gui\UXMenu ot php\gui\UXMenuBar, now $target = ' . get_class($target));
        }
        
        return new self($target, $config);
    }
    
    private function __construct ($target, $config) {
        $this->target = $target;
        $this->config = $config;
    }
    
    /**
     * @return UXMenuItem
     */
    public function addItem ($text, $callback, UXNode $graphic = null) {
        $this->target->items->add($node = new UXMenuItem($text));
        $this->setGraphic($node, $graphic);
        $node->on('action', $callback);
        
        return $node;
    }
    
    /**
     * @return ContextMenuHelper
     */
    public function addCategory ($text, callable $callback = null, UXNode $graphic = null) {
        $param = 'items';
        
        if ($this->target instanceof UXMenuBar) {
            $param = 'menus';
        }
        $this->target->{$param}->add($node = new UXMenu());
        $this->setGraphic($node, $label = new UXLabel($text));
        
        if ($callback !== null) {
            $label->on("click", $callback);
        }
        
        return ContextMenuHelper::of($node, $this->config);
    }
    
    public function getTarget () {
        return $this->target;
    }
    
    public function addSeparator () {
        $this->target->items->add(UXMenu::createSeparator());
    }
    
    /**
     * @return UXImageView
     */
    public function makeIcon ($file) {
        return new UXImageView(new UXImage($file));
    }
    
    
    
    
    
    private function setGraphic ($node, $graphic) {
        if ($graphic !== null) {
            $node->graphic = $graphic;
        }
        
        if ($this->config instanceof Configuration) $this->configurate($node);
    }
    
    private function configurate ($node) {
        if ($node->graphic === null) return;
        
        if ($this->config->has(ContextMenuHelper::GRAPHIC_WIDTH)) {
            $node->graphic->width =  $this->config->get(ContextMenuHelper::GRAPHIC_WIDTH);
        }
        
        if ($this->config->has(ContextMenuHelper::GRAPHIC_HEIGHT)) {
            $node->graphic->height =  $this->config->get(ContextMenuHelper::GRAPHIC_HEIGHT);
        }
    }
}