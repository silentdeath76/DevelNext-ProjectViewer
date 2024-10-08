<?php
namespace app\ui\overlayContainers;

use framework;
use app;
use gui;

class AboutContainer extends AbstractNode
{
    private $title;
    private $source;
    private $author;
    private $version;
    private $_version;
    
    private $spacing = 3;
    private $paddingLeft = 20;
    
    protected function make () {
        $this->container = new UXVBox();
        $this->container->width = 300;
        $this->container->alignment = 'CENTER';
        $this->container->spacing = 10;
        $this->container->classes->add('about');
        
        $shadow = new DropShadowEffectBehaviour();
        $shadow->offsetX = 0;
        $shadow->offsetY = 2;
        $shadow->radius = 2;
        $shadow->color = '#000000';
        $shadow->apply($this->container);
        
        $this->title = new UXLabelEx(AppModule::APP_TITLE);
        $this->title->width = $this->container->width;
        $this->title->alignment = 'CENTER';
        $this->title->classes->add("title");
        
        $this->source = new UXHBox([new UXLabelEx(Localization::get('ui.about.site')), $link = new UXHyperlink("GitHub")]);
        $this->source->spacing = $this->spacing;
        $this->source->paddingLeft = $this->paddingLeft;
        $this->source->paddingTop = 20;
        $link->on("click", function () {
            browse('https://github.com/silentdeath76/DevelNext-ProjectViewer');
        });
        
        $this->author = new UXHBox([new UXLabelEx(Localization::get('ui.about.author')), $link = new UXHyperlink("Vk")]);
        $this->author->spacing = $this->spacing;
        $this->author->paddingLeft = $this->paddingLeft;
        $link->on("click", function () {
            browse('https://vk.com/silentrs');
        });
        
        $this->version = new UXHBox([new UXLabelEx(Localization::get('ui.about.version')), $this->_version = new UXLabelEx(AppModule::APP_VERSION)]);
        $this->version->spacing = $this->spacing;
        $this->version->paddingLeft = $this->paddingLeft;
        
        
        $this->container->add($this->title);
        $this->container->add($this->source);
        $this->container->add($this->author);
        $this->container->add($this->version);
    }
    
    public function updateVersion ($version) {
        $this->_version->text = $version;
    }
    
    public function updateTitle ($title) {
        $this->title->text = $title;
    }
    
    
}