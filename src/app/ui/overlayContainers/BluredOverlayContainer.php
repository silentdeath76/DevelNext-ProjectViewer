<?php
namespace app\ui\overlayContainers;

use Exception;
use std;
use framework;
use gui;
use app;

class BluredOverlayContainer extends OverlayContainer
{
    /**
     * @var UXAnchorPane
     */
    protected $container;
    
    /**
     * @var UXHBox
     */
    protected $overlay;
    
    /**
     * @var UXAnchorPane
     */
    protected $bluredContainer;
    
    
    private $blurRadius = 5;
    
    private $targetChildren;
     
    protected function make () {
        $this->container = new UXAnchorPane();
        $this->container->leftAnchor = $this->container->topAnchor = $this->container->rightAnchor = $this->container->bottomAnchor = 0;
        
        $binder = new EventBinder($this->container);
        $binder->bind("construct", function () {
            $this->targetChildren = $this->container->parent->children->toArray();
            
            foreach ($this->targetChildren as $key => $node) {
                if ($node === $this->container) {
                    unset($this->targetChildren[$key]);
                    $this->bluredContainer->children->addAll($this->targetChildren); // фикс первого появления: не применяется размытие т.к. событие construct срабатывает позже чем вызов метода show
                    break;
                }
            }
        });
        
        $this->bluredContainer = new UXAnchorPane();
        $this->bluredContainer->leftAnchor = $this->bluredContainer->topAnchor = $this->bluredContainer->rightAnchor = $this->bluredContainer->bottomAnchor = 0;
        $this->bluredContainer->mouseTransparent = true;
        
        $blur = new GaussianBlurEffectBehaviour();
        $blur->radius = $this->blurRadius;
        $blur->apply($this->bluredContainer);
        
        $this->overlay = new UXHBox();
        $this->overlay->alignment = 'CENTER';
        $this->overlay->leftAnchor = $this->overlay->topAnchor = $this->overlay->rightAnchor = $this->overlay->bottomAnchor = 0;
        $this->overlay->style= "-fx-background-color: " . $this->overlayBackground . ";";
        $this->overlay->on("click", function () {
            $this->bluredContainer->children->clear();
            
            try {
                $this->container->parent->children->addAll($this->targetChildren);
            } catch (Exception $ignore) {}
            
            $this->container->hide();
        });
        
        $this->container->add($this->bluredContainer);
        $this->container->add($this->overlay);
    }
    
    public function addContent (AbstractNode $node) {
        $this->overlay->add($node->getNode());
    }
    
    public function show () {
        $this->container->show();
        $this->bluredContainer->children->addAll($this->targetChildren);
    }
}