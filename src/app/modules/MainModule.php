<?php
namespace app\modules;

use Exception;
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
            // закгрузка старых размеров формы если она была раpвернута на весь экран

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
    
    public function errorAlert (Exception $ex, $detailed = false) {
        $this->logger->discord("```json\n" . $ex->getMessage() . "```\n" . $ex->getTraceAsString(), LoggerReporter::ERROR)->send();
        
        $alert = new UXAlert("ERROR");
        $alert->headerText = "";
        
        if ($detailed) {
            $alert->expanded = false;
            $alert->expandableContent = new UXScrollPane(new UXAnchorPane);
            $alert->expandableContent->height = 400;
            $alert->expandableContent->content->add(new UXLabel(var_export($ex->getTraceAsString(), true)));
        }
        
        $alert->title = Localization::get('message.error');
        $alert->contentText = $ex->getMessage();
        $alert->show();
    }
    
    
    public static function replaceSeparator ($string) {
        return str_replace(['\\', '/'], File::DIRECTORY_SEPARATOR, $string);
    }
    
    
    public function _showForm ($formData, $outputImage) {
        $n = new Environment();
        $n->importAutoLoaders();
        
        $n->importClass(MainForm::class);
        $n->importClass(MainModule::class);
        
        $n->execute(function () use ($formData, $outputImage) {
            $layout = new UXLoader()->loadFromString($formData);
            $form = new UXForm();
            $form->add($layout);
            $outputImage->image = $form->layout->snapshot();
        });
    }
    
    public function firstRunReport () {
        if ($this->ini->get("firstRun") != 1) {
            $this->ini->set("firstRun", 1);
            $this->logger->discord(sprintf("App has ben installed; User: %s;", System::getProperty("user.name")), LoggerReporter::INFO)->send();
        }
    }
}