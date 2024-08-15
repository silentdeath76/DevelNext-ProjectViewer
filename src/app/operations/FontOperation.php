<?php
namespace app\operations;

use framework;
use std;
use gui;
use app;

class FontOperation extends AbstractOperation
{
    public function getActiveTab ()
    {
        return self::VIEW;
    }
    
    public function forExt ()
    {
        return ['ttf', 'otf'];
    }
    
    public function action ()
    {
        $font = UXFont::load($this->output, 24);
        
        if ($font === null) {
            app()->form("MainForm")->logger->console("Broken font file or not supported type", LoggerReporter::ERROR)->show();
            return;
        }
        
        app()->form("MainForm")->image->mouseTransparent = true;
        
        $gc = app()->form("MainForm")->image->getGraphicsContext();
        $gc->clearRect(0, 0, app()->form("MainForm")->image->width, app()->form("MainForm")->image->height);
        
        $gc->font = $font;
        
        if (app()->form("MainForm")->data('theme') == 'dark' || app()->form("MainForm")->data('theme') == 'nord') {
            $gc->fillColor = 'white';
        } else {
            $gc->fillColor = 'dark';
        }
        
        for ($i = a; $i < z; $i++) {
            $text .= $i;
        }
        
        $text .= "\n";
        
        for ($i = A; $i < Z; $i++) {
            $text .= $i;
        }
        
        $text .= "\n";
        
        for ($i = 0; $i < 9; $i++) {
            $text .= $i;
        }
        
        $abc = ["а", "б", "в", "г", "д", "е", "ё", "ж", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы", "ь", "э", "ю", "я"];
        
        $text .= "\n";
        $text .= flow($abc)->toString("");
        $text .= "\n";
        
        
        $text .= flow($abc)->map(function ($v) {
            return str::upper($v);
        })->toString("");
        
        $gc->fillText($text, 10, 34);
        $gc->fill();
        
    }
}