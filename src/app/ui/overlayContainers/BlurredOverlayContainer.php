<?php
namespace app\ui\overlayContainers;

use Exception;
use std;
use framework;
use gui;
use app;

class BlurredOverlayContainer extends OverlayContainer
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
    protected $blurredContainer;
    
    
    private $blurRadius = 5;
    
    private $targetChildren;
     
    protected function make () {
        $this->container = $this->makeAnchorPane();
        
        $binder = new EventBinder($this->container);
        $binder->bind("construct", function () {
            $this->targetChildren = $this->container->parent->children->toArray();
            
            foreach ($this->targetChildren as $key => $node) {
                if ($node === $this->container) {
                    unset($this->targetChildren[$key]);
                    $this->blurredContainer->children->addAll($this->targetChildren); // фикс первого появления: не применяется размытие т.к. событие construct срабатывает позже чем вызов метода show
                    break;
                }
            }
        });
        
        $this->blurredContainer = $this->makeAnchorPane();
        $this->blurredContainer->mouseTransparent = true;
        
        $this->applyBlur($this->blurredContainer);
        
        $this->overlay = new UXHBox();
        $this->overlay->alignment = 'CENTER';
        $this->overlay->leftAnchor = $this->overlay->topAnchor = $this->overlay->rightAnchor = $this->overlay->bottomAnchor = 0;
        $this->overlay->style= "-fx-background-color: " . $this->overlayBackground . ";";
        $this->overlay->on("click", function () {
            $this->blurredContainer->children->clear();
            
            try {
                $this->container->parent->children->addAll($this->targetChildren);
            } catch (Exception $ignore) {}
            
            $this->container->hide();
        });
        
        $this->container->add($this->blurredContainer);
        $this->container->add($this->overlay);
    }
    
    public function addContent (AbstractNode $node) {
        $this->overlay->add($node->getNode());
    }
    
    public function show () {
        $this->container->show();
        $this->blurredContainer->children->addAll($this->targetChildren);
    }
    
    
    /**
     * @return UXAnchorPane
     */
    private function makeAnchorPane () {
        $pane = new UXAnchorPane();
        $pane->leftAnchor = $pane->topAnchor = $pane->rightAnchor = $pane->bottomAnchor = 0;
        
        return $pane;
    }
    
    private function applyBlur ($container) {
        $blur = new GaussianBlurEffectBehaviour();
        $blur->radius = $this->blurRadius;
        $blur->apply($container);
    }
}