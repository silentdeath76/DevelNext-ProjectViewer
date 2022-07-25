<?php
namespace app\modules;

use httpclient;
use std, gui, framework, app;


class AppModule extends AbstractModule
{
    const SELF_UPDATE_DELAY = 10000;
    const APP_VERSION = '1.1.3';
    const APP_TITLE = 'DevelNext ProjectView';
    
    /**
     * @var Thread
     */
    private $executer;

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
        $form->title = AppModule::APP_TITLE;
        
        $form->show();
        
        $this->executer = new Thread(function () {
            Thread::sleep(AppModule::SELF_UPDATE_DELAY);
            $this->update();
        });
        
        $this->executer->setDaemon(true);
        $this->executer->start();
    }
    
    
    public function update () {
        $temp = fs::normalize(System::getProperty('user.home') . '\AppData\Local\Temp');
        
        Logger::info('Checking updates...');

        $selfUpdate = new Selfupdate('silentdeath76', 'DevelNext-ProjectViewer');
        $response = $selfUpdate->getLatest();
        
        if (str::compare(AppModule::APP_VERSION, $response->getVersion()) == -1) {
            Logger::info('Have new version');
            Logger::info(sprintf("Current version %s new version %s", AppModule::APP_VERSION, $response->getVersion()));
            
            $tempFile = fs::normalize($temp . '/' . $response->getName());
            
            $http = new HttpClient();
            $stream = $http->get($response->getLink())->body();
            $outputStream = new FileStream($tempFile, "w+");
            $outputStream->write($stream);
            $outputStream->close();

            fs::copy($tempFile, './' . $response->getName());

            if (File::of('./' . $response->getName())->exists()) {
                execute(fs::abs('./' . $response->getName()));
            }

            app()->shutdown();
        }
        
        unset($selfUpdate);
        unset($response);
        unset($this->executer);
    }

}
