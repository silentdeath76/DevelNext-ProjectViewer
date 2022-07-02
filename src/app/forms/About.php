<?php
namespace app\forms;

use std, gui, framework, app;


class About extends AbstractForm
{

    /**
     * @event vbox.construct 
     */
    function doVboxConstruct(UXEvent $e = null)
    {    
        $e->sender->add($container = new UXHBox([new UXLabel("Сайт: "), $link = new UXHyperlink("GitHub")]));
        $container->style = '-fx-font-size: 16px';
        $container->spacing = 5;
        $link->on("action", function () {
            open('https://github.com/silentdeath76/DevelNext-ProjectViewer');
        });
        
        $e->sender->add($container = new UXHBox([new UXLabel("Автор: "), $link = new UXHyperlink("Vk")]));
        $container->style = '-fx-font-size: 16px';
        $container->spacing = 5;
        $link->on("action", function () {
            open('https://vk.com/silentrs');
        });
    }

}
