<?php
namespace app\modules;

use std, gui, framework, app;


class MainModule extends AbstractModule
{
    const EXECUTE_AFTER_TIME = 1000;
    
    private $needLoad = false;
    private $formWidth;
    private $formHeight;
    
    public function formSizeSaver () {
        $this->form("MainForm")->observer("width")->addListener(function ($o, $n)  {
            static $timer;
            
            if ($timer instanceof AccurateTimer) {
                $timer->reset();
            } else {
                $timer = AccurateTimer::executeAfter(self::EXECUTE_AFTER_TIME, function () use (&$timer) {
                    $this->ini->set("maximized", $this->maximized ? 1 : 0);

                    unset($timer);
                    if ($this->maximized) return;
                    
                    $this->ini->set("width", $this->width);
                });
            }
        });
        
        
        $this->form("MainForm")->observer("height")->addListener(function ($o, $n)  {
            static $timer;
            
            if ($timer instanceof AccurateTimer) {
                $timer->reset();
            } else {
                $timer = AccurateTimer::executeAfter(self::EXECUTE_AFTER_TIME, function () use (&$timer) {
                    $this->ini->set("maximized", $this->maximized ? 1 : 0);
                    
                    unset($timer);
                    if ($this->maximized) return;
                    
                    $this->ini->set("height", $this->height);
                });
            }
        });
        
        
        $this->form("MainForm")->observer("maximized")->addListener(function ($o, $n) {
            // закгрузка старых размеров формы если она была равзернута на весь экран
            if ($n === true) {
                $this->needLoad = true;
                $this->formWidth = $this->ini->get('width');
                $this->formHeight = $this->ini->get('height');
            } else {
                if ($this->needLoad) {
                    $this->needLoad = false;
                    $this->form("MainForm")->width = $this->formWidth;
                    $this->form("MainForm")->height = $this->formHeight;
                    $this->form("MainForm")->centerOnScreen();
                }
            }
            
        });
    }
}