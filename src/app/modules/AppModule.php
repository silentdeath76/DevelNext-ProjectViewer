<?php
namespace app\modules;

use std, gui, framework, app;


class AppModule extends AbstractModule
{

    /**
     * @event construct 
     */
    function doConstruct(ScriptEvent $e = null)
    {    
        // call garbage collector every 30s
        Timer::every(30000, function () {System::gc(); });
    }

    /**
     * @event action 
     */
    function doAction(ScriptEvent $e = null)
    {    
        // Чтобы форма не мелькала при ресайзе окна
        $form = $this->form("MainForm");
        $form->minWidth = 900;
        $form->minHeight = 450;
        $form->opacity = 0;
        
        $form->show();
    }

}
