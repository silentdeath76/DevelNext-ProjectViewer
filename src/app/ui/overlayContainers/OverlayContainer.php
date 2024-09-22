<?php
namespace app\ui\overlayContainers;

use app;
use gui;

class OverlayContainer extends AbstractNode
{
    protected $overlayBackground = '#000010cF';
    
    protected function make () {
        $this->container = new UXHBox();
        $this->container->alignment = 'CENTER';
        $this->container->leftAnchor = 0;
        $this->container->rightAnchor = 0;
        $this->container->topAnchor = 0;
        $this->container->bottomAnchor = 0;
        $this->container->style = "-fx-background-color: " . $this->overlayBackground . ";";
        $this->container->on("click", function () {
            $this->container->hide();
        });
    }
    
    public function addContent (AbstractNode $node) {
        $this->container->add($node->getNode());
    }
    
    public function show () {
        $this->container->show();
    }
}