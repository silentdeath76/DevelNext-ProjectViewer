<?php
namespace app\modules;

use bundle\windows\Registry;
use std, gui, framework, app;


class MainModule extends AbstractModule
{
    const EXECUTE_AFTER_TIME = 1000;
    
    public function formSizeSaver (Registry $reg) {
        $this->form("MainForm")->observer("width")->addListener(function ($o, $n) use ($reg) {
            static $timer;
            
            if ($timer instanceof AccurateTimer) {
                $timer->reset();
            } else {
                $timer = AccurateTimer::executeAfter(self::EXECUTE_AFTER_TIME, function () use (&$timer, $reg) {
                    $reg->add("width", $this->width);
                    $reg->add("maximized", $this->maximized ? 1 : 0, "REG_DWORD");
                    unset($timer);
                });
            }
        });
        
        
        $this->form("MainForm")->observer("height")->addListener(function ($o, $n) use ($reg) {
            static $timer;
            
            if ($timer instanceof AccurateTimer) {
                $timer->reset();
            } else {
                $timer = AccurateTimer::executeAfter(self::EXECUTE_AFTER_TIME, function () use (&$timer, $reg) {
                    $reg->add("height", $this->height);
                    $reg->add("maximized", $this->maximized ? 1 : 0, "REG_DWORD");
                    unset($timer);
                });
            }
        });
    }
}