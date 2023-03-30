<?php
namespace app\modules;

use Exception;
use httpclient;
use std, gui, framework, app;


class AppModule extends AbstractModule
{
    const SELF_UPDATE_DELAY = 10000;
    const APP_VERSION = '1.1.6';
    const APP_TITLE = 'DevelNext ProjectView';
    
    const UPDATE_TEMP_PATH = '\AppData\Local\Temp';
    const FOUND_NEW_VERSION = -1;
    
    const WINDOW_MIN_WIDTH = 900;
    const WINDOW_MIN_HEIGHT = 450;
    
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
        $theme = app()->module("MainModule")->ini->get("theme") ?: 'light';
        // Чтобы форма не мелькала при ресайзе окна
        $form = $this->form("MainForm");
        $form->minWidth = AppModule::WINDOW_MIN_WIDTH;
        $form->minHeight = AppModule::WINDOW_MIN_HEIGHT;
        $form->opacity = 0;
        $form->title = AppModule::APP_TITLE;
        $form->data('theme', $theme);
        
        $form->show();
        
        $this->executer = new Thread(function () {
            Thread::sleep(AppModule::SELF_UPDATE_DELAY);
            $this->update();
            
            /* 
            uiLater(function () {
                $notify = new UXHBox();
                $notify->spacing = 5;
                $notify->fillHeight = true;
                $notify->add($button = new UXFlatButton("🗙"));
                $button->textColor = "white";
                $button->hoverColor = 'gray';
                $notify->rightAnchor = 10;
                $notify->topAnchor = 5;
                $notify->y = 5;
                
                $notify->add($text = new UXLabel("Есть новая версия программы."));
                $notify->add($updateButton = new UXFlatButton("Обновить"));
                $updateButton->hoverColor = '#FFFFFF1c';
                $updateButton->textColor = "white";
                $updateButton->paddingLeft = 5;
                $updateButton->paddingRight = 5;
                app()->form("MainForm")->add($notify);
                
                $notify->x = app()->form("MainForm")->width;
                $width = UXFont::getDefault()->calculateTextWidth($button->text) +
                    UXFont::getDefault()->calculateTextWidth($text->text) +
                    UXFont::getDefault()->calculateTextWidth($updateButton->text) + $notify->spacing * 4;
                
                Animation::displace($notify, 1000, $width * -1 - 10, 0, function () use ($notify) {
                    
                });
            });
            
            */
        });
        
        $this->executer->setDaemon(true);
        $this->executer->start();
    }

    
    
    public function update () {
        $temp = fs::normalize(System::getProperty('user.home') . self::UPDATE_TEMP_PATH);
        
        Logger::info('Checking updates...');

        try {
            $selfUpdate = new Selfupdate('silentdeath76', 'DevelNext-ProjectViewer');
        } catch (Exception $ex) {
            Logger::error($ex->getMessage());
            return;
        }
        
        try {
            $response = $selfUpdate->getLatest();
            
            if (str::compare(AppModule::APP_VERSION, $response->getVersion()) == AppModule::FOUND_NEW_VERSION) {
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
        
        } catch (Exception $ex) {
            uiLater(function () use ($ex) {
                app()->form("MainForm")->errorAlert($ex, true);
            });
        } finally {
            unset($selfUpdate);
            unset($response);
            unset($this->executer);
        }
    }

}
