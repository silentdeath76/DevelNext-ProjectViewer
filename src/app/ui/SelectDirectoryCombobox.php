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
        $this->container->y = 32;
        $this->container->id = 'combobox';
        
        $this->configurateConfig();
        
        $event = new EventBinder($this->container);
        $event->bind('construct', function () {
            $this->applyAnimation();
        });
        
        // событие смены значения
        $this->container->observer('value')->addListener(function ($old, $new) {
            app()->form("MainForm")->tree->root->children->clear();
            app()->form("MainForm")->ini->set('ProjectDirectory', $new[0]);
            app()->form("MainForm")->fsTree->setDirectory($new[0]);
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
        if (count(app()->form("MainForm")->ini->get('directoryList')) == 0 && app()->form("MainForm")->projectDir != null) {
            app()->form("MainForm")->ini->set("directoryList", [app()->form("MainForm")->projectDir]);
        }
        
        // если список является массивом, проходимся циклом по элементам и добавляем их
        // елси путь совпадает с тем что находится в свойстве projectDir то делаем его активным
        if (is_array(app()->form("MainForm")->ini->get('directoryList'))) {
            foreach (app()->form("MainForm")->ini->get('directoryList') as $key => $path) {
                $this->container->items->add([$path, '.directory-icon']);
                if ($path == app()->form("MainForm")->projectDir) {
                    app()->form("MainForm")->tree->root->children->clear();
                    $this->container->selectedIndex = $key;
                    app()->form("MainForm")->fsTree->setDirectory($path);
                }
            }
        } else {
            // если список не является массивом, и при этом не пустой
            // то добовляем это значение и получаем список директорий
            if (app()->form("MainForm")->ini->get('directoryList') != null) {
                $this->container->items->add([app()->form("MainForm")->ini->get('directoryList'), '.directory-icon']);
                $this->container->selectedIndex = 0;
                app()->form("MainForm")->fsTree->setDirectory(app()->form("MainForm")->ini->get('directoryList'));
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
}