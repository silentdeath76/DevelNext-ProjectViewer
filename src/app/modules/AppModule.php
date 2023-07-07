<?php
namespace app\modules;

use Exception;
use httpclient;
use std, gui, framework, app;


class AppModule extends AbstractModule
{
    const SELF_UPDATE_DELAY = 10000;
    const APP_VERSION = '1.1.5';
    const APP_TITLE = 'DevelNext ProjectView';
    
    const UPDATE_TEMP_PATH = '\AppData\Local\Temp';
    const FOUND_NEW_VERSION = -1;
    
    const WINDOW_MIN_WIDTH = 900;
    const WINDOW_MIN_HEIGHT = 450;
    
    /**
     * @var Thread
     */
    private $executer;
    
    private $temp;
    
    /**
     * @var UXHBox
     */
    public $notifyContainer;

    /**
     * @event construct 
     */
    function doConstruct(ScriptEvent $e = null)
    {    
        $this->temp = str_replace(['\\', '/'], File::DIRECTORY_SEPARATOR, System::getProperty('user.home') . self::UPDATE_TEMP_PATH);
        
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
        
        $this->executer = new Thread(function () use ($form) {
            $file = $this->temp . File::DIRECTORY_SEPARATOR . fs::name($GLOBALS["argv"][0]);
            
            if (fs::exists($file)) {
                Thread::sleep(1000);
                Logger::info('update from already downloaded file');
                
                try {
                    $this->moveFile($file, './' . fs::name($GLOBALS["argv"][0]));
                    execute(fs::abs('./' . fs::name($GLOBALS["argv"][0])));
                } catch (Exception $ex) {
                    Logger::error($ex->getMessage());
                }
                
                app()->shutdown();
                return;
            }
            
            Thread::sleep(AppModule::SELF_UPDATE_DELAY);
            
            $this->update();
        });
        
        $this->executer->setDaemon(true);
        $this->executer->start();
    }

    
    
    public function update () {
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
                $form = app()->form("MainForm");
                Logger::info('Found new version');
                Logger::info(sprintf("Current version %s; new version %s;", AppModule::APP_VERSION, $response->getVersion()));
                
                $tempFile = str_replace(['\\', '/'], File::DIRECTORY_SEPARATOR, $this->temp . '/' . $response->getName());
                
                $http = new HttpClient();
                $stream = $http->get($response->getLink())->body();
                $outputStream = new FileStream($tempFile, "w+");
                $outputStream->write($stream);
                $outputStream->close();
                
                uiLater(function () use ($form, $response, $tempFile) {
                    $this->showUpdateNotify("Найдена новая версия программы" . '   ', "Обновить", $form->infoPanelSwitcher, function () use ($response, $tempFile) {
                        $th = new Thread(function () use ($response, $tempFile) {
                            $this->moveFile($tempFile, './' . $response->getName());
                            execute(fs::abs('./' . $response->getName()));
                            app()->shutdown();
                        });
                        $th->setDaemon(true);
                        $th->start();
                    });
                    
                    $form->tabPane->toBack();
                });
                
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
    
    private function moveFile ($from, $to) {
        fs::copy($from, $to);
        
        if (File::of($to)->exists()) {
            fs::delete($from);
        }
    }
    
    public function showUpdateNotify ($text, $buttonText, UXRegion $target, callable $callback, $customPadding = 0) {
        static $container;
        
        if ($container == null) {
            $container = new UXHBox();
            $container->add($label = new UXLabelEx($text));
            $container->add($button = new UXFlatButton($buttonText));
            $container->spacing = 5;
            $container->padding = 5;
            $container->paddingRight = $target->width + 10;
            $container->alignment = "CENTER_LEFT";
            $container->maxWidth = 0;
            $container->style = '-fx-background-radius: 10 25 25 10; -fx-border-radius: 10 25 25 10; -fx-background-color: #0000002F';
            $container->minHeight = $target->height;
            $container->opacity = 0;
            $container->x = $target->x + $container->width;
            $container->y = $target->y-1;
            
            $label->ellipsisString = null;
            $label->autoSize = true;
            $label->autoSizeType = 'HORIZONTAL';
            
            $button->backgroundColor = '#00000000';
            $button->hoverColor = '#0000001F';
            $button->clickColor = ' #0000000F';
            $button->borderRadius = 3;
            $button->padding = 3;
            $button->font->bold = true;
            $button->width = 90;
            $button->alignment = 'CENTER';
            
            $button->ellipsisString = null;
            $button->on("click", $callback);
            
            $target->data("container", $container);
            $target->parent->add($container);
            
            $container->toBack();
            
            $this->notifyContainer = $container;
        }
        
        $container->maxWidth = 0;
        
        $toSize = UXFont::getDefault()->calculateTextWidth($text) + UXFont::getDefault()->calculateTextWidth($buttonText) + 30 + $container->paddingRight + $customPadding;
        $show = new UXAnimationTimer(function () use (&$show, $container, $toSize, $target, $customPadding) {
            $step = 20;
            $container->maxWidth += $step;
            $container->width += $step;
            $container->x = $target->x + $target->width - $container->width + 1;
            $container->opacity += 0.2;
            
            if ($container->maxWidth > $toSize) {
                $show->stop();
            }
        });
        
        $show->start();
    }

}
