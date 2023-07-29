<?php
namespace app\ui;

use framework;
use app;
use gui;

class SelectDirectoryCombobox extends AbstractNode
{
    /**
     * @var UXComboBox
     */
    protected $container;
    
    protected function make () {
        $this->container = new UXComboBox();
        $this->container->width = 256;
        $this->container->height = 54;
        $this->container->leftAnchor = 0;
        $this->container->rightAnchor = 0;
        $this->container->y = 8;
        $this->container->id = 'combobox';
        
        $this->configurateConfig();
        
        $event = new EventBinder($this->container);
        $event->bind('construct', function () {
            $this->applyAnimation();
        });
        
        // событие смены значения
        $this->container->observer('value')->addListener(function ($old, $new) {
            $this->getForm()->tree->root->children->clear();
            $this->getForm()->ini->set('ProjectDirectory', $new[0]);
            $this->getForm()->fsTree->setDirectory($new[0]);
        });
        
        // событие обрботки элементов прежде чем они попадут в список
        $this->container->onCellRender(function (UXListCell $cell, $node, bool $selected = null) {
            $item = new DirectoryItem();
            $item->setText($node[0]);
            $item->setImage('directory-icon');

            $cell->text = "";
            $cell->graphic = $item->getNode();
        });
        
        // событие отрисовки элемента после выбора его из списка 
        $this->container->onButtonRender(function (UXListCell $cell, $node) use () {
            $item = new SelectedDirectoryItem();
            $item->setText($node[0], 0);
            $item->setTitle(Localization::get("ui.directorySwitcher.activeDirectory"));
            $item->setImage('directory-icon');

            $cell->graphic = $item->getNode();
        });
        
    }
    
    private function configurateConfig () {
        // если не является списком или пустой, то устанавливаем значение в списко из свойства projectDir
        if (count($this->getForm()->ini->get('directoryList')) == 0 && $this->getForm()->projectDir != null) {
            $this->getForm()->ini->set("directoryList", [$this->getForm()->projectDir]);
        }
        
        // если список является массивом, проходимся циклом по элементам и добавляем их
        // елси путь совпадает с тем что находится в свойстве projectDir то делаем его активным
        if (is_array($this->getForm()->ini->get('directoryList'))) {
            foreach ($this->getForm()->ini->get('directoryList') as $key => $path) {
                $this->container->items->add([$path, '.directory-icon']);
                if ($path == $this->getForm()->projectDir) {
                    $this->getForm()->tree->root->children->clear();
                    $this->container->selectedIndex = $key;
                    $this->getForm()->fsTree->setDirectory($path);
                }
            }
        } else {
            // если список не является массивом, и при этом не пустой
            // то добовляем это значение и получаем список директорий
            if ($this->getForm()->ini->get('directoryList') != null) {
                $this->container->items->add([$this->getForm()->ini->get('directoryList'), '.directory-icon']);
                $this->container->selectedIndex = 0;
                $this->getForm()->fsTree->setDirectory($this->getForm()->ini->get('directoryList'));
            }
        }
    }
    
    private function applyAnimation () {
        $arrow = $this->container->lookup('.arrow');
        $arrow->rotate = 90;
        
        // анимация вращения стрелочки с последующей сменой на крест
        // сама смена просиходит в стилях
        $this->container->observer('showing')->addListener(function ($old, $new) use ($arrow) {
            $speed = 1000;
            $minangle = 90;
            $maxangle = 270;
            
            $timer = new UXAnimationTimer(function () use (&$timer, $arrow, $new, $speed, $minangle, $maxangle) {
                if ($new) {
                    $arrow->rotate += $speed * UXAnimationTimer::FRAME_INTERVAL;
                } else {
                    $arrow->rotate -= $speed * UXAnimationTimer::FRAME_INTERVAL;
                }
                
                if ($arrow->rotate % $maxangle == 0 || $arrow->rotate <= $minangle) {
                    $timer->stop();
                }
                
                if ($arrow->rotate > $maxangle) $arrow->rotate = $maxangle;
            });
            
            $timer->start();
        });
    }

    private function getForm () {
        return app()->form("MainForm");
    }
}