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
        $this->label->text = AppModule::APP_TITLE;
        
        $style = '-fx-font-size: 16px';
        
        $e->sender->add($container = new UXHBox([new UXLabel("Сайт: "), $link = new UXHyperlink("GitHub")]));
        $container->style = $style;
        $container->spacing = 5;
        $link->on("action", function () {
            open('https://github.com/silentdeath76/DevelNext-ProjectViewer');
        });
        
        $e->sender->add($container = new UXHBox([new UXLabel("Автор: "), $link = new UXHyperlink("Vk")]));
        $container->style = $style;
        $container->spacing = 5;
        $link->on("action", function () {
            open('https://vk.com/silentrs');
        });
        
        $e->sender->add($container = new UXHBox([new UXLabel("Версия: "), new UXLabel(AppModule::APP_VERSION)]));
        $container->style = $style;
    }

}
