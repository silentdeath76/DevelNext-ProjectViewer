<?php
namespace app;

use Exception;
use framework;
use gui;
use std;
use php\compress\ZipFile;

class Dependency 
{
    const DEFAULT_DEPENDENCY_PATH = '\DevelNextLibrary\bundles\\';
    const DEPENDENCY_ICON_PATH = '.data/img/develnext/bundle/';
    
    public function getDependencys (ZipFile $zip) {
        app()->form("MainForm")->flowPane->children->clear();
        
        $panels = [];
        
        foreach ($zip->statAll() as $path => $state) {
            if (str::startsWith($path, '.dn/bundle')) {
                // стандартные пакеты которые есть во всех проектах и которые нас не интересуют
                if (str::endsWith($path, 'ide.bundle.std.JPHPDesktopDebugBundle.conf') or str::endsWith($path, 'ide.bundle.std.UIDesktopBundle.conf')) continue;
                
                $zip->read($path, function (array $state, Stream $stream) use (&$panels) {
                    foreach (explode("\n", $stream) as $line) {
                        if (str::startsWith(trim($line), 'name')) {
                            $name = trim(explode('=', $line)[1]);
                            $image = $this->getIcon($name);
                            $panels[] = $this->makeUi($image, $name);
                        }
                    }
                });
            }
        }
        
        /* uasort($panels, function ($a, $b) {
            $a = $a->children->offsetGet(1)->font->calculateTextWidth($a->children->offsetGet(1)->text) + 16;
            $b = $b->children->offsetGet(1)->font->calculateTextWidth($b->children->offsetGet(1)->text) + 16;
            
            return str::compare($a, $b);
        }); */
        
        $sort = [];
        $pWidt = app()->form("MainForm")->fileInfo->width - 10;
        
        $panels = array_reverse($panels);
        
        foreach ($panels as $panel) {
            $width = $panel->children->offsetGet(1)->font->calculateTextWidth($panel->children->offsetGet(1)->text) + 16;
            
            if ($panel->children->count() > 2) { // if have link on github
                $width += 16;
            }
            
            
            foreach ($sort as $key => $_panel) {
                if (count($_panel) >= 1) {
                    $lWidth = 0;
                    
                    foreach ($_panel as $item) {
                        $lWidth += $item->children->offsetGet(1)->font->calculateTextWidth($item->children->offsetGet(1)->text) + 16;
                        
                        if ($item->children->count() > 2) {
                            $lwidth += 16;
                        }
                        
                    }
                    
                    if (($width + $lWidth) < $pWidt) {
                        $sort[$key][] = $panel;
                        continue 2;
                    } else {
                        $sort[] = [$panel];
                    }
                }
            }
            
            $sort[] = [$panel];
        }
        
        
        
        foreach (array_reverse($sort) as $l) {
            try {
                app()->form("MainForm")->flowPane->children->addAll($l);
            } catch (Exception $ignore) {}
        }
    }

    
    public function makeUi ($image, $name) {
        if (!($image instanceof UXImage)) {
            $image = new UXImage('res://.data/img/ui/image-16.png');
        }
        
        $panel = new UXHBox();
        $panel->padding = 3;
        $panel->spacing = 5;
        
        $panel->add($view = new UXImageView($image));
        $view->x = 4;
        $view->y = 4;
        $view->width = 16;
        $view->height = 16;
        
        $panel->add($label = new UXLabelEx($name));
        $label->autoSize = true;
        $label->autoSizeType = 'HORIZONTAL';
        $label->x = 24;
        $label->y = 4;
        $panel->classes->add('DependencyItem');
        
        if (($url = $this->getLink($name)) != false) {
            $width = 10;
            $height = 10;
            
            $panel->add($link = new UXImageView(new UXImage('res://.data/img/ui/external-link-16.png', $width, $height)));
            $link->cursor = 'HAND';
            
            $link->on("click", function () use ($url) {
                if (uiConfirm('Открыть ссылку в браузере?')) {
                    open($url);
                }
            });
            $link->on("mouseEnter", function () use ($link, $width, $height) {
                static $image =  new UXImage('res://.data/img/ui/external-link-16 hover.png', $width, $height);
                $link->image = $image;
            });
            $link->on("mouseExit", function () use ($link, $width, $height) {
                static $image = new UXImage('res://.data/img/ui/external-link-16.png', $width, $height);;
                $link->image = $image;
            });
        }
        
        return $panel;
    }
    
    private function getLink($name) {
        static $json = json_decode(FileStream::of('res://.data/dependencys.json'), true);
        
        $found = array_column($json, null, 'name');
        
        if (is_null($found[$name])) {
            /** default extensions */
            switch ($name) {
                case '2D Game':
                case 'Hot Key':
                case 'HTTP Client':
                case 'Material UI':
                case 'JSoup':
                case 'Mailer': 
                case 'FireBird SQL':
                case 'MySQL': 
                case 'PostgreSQL':
                case 'SQLite':
                case 'System Tray':
                case 'ZIP': return false;
            }
            
            Logger::info($name);
            
            return false;
        }
        
        return $found[$name]["link"];
    }
    
    /**
     * Иконки стандартных пакетов DN
     * 
     * @param string $bundleName
     */
    public function getIcon ($bundleName) {
        $name = 'res://.data/img/bundle/';
        
        switch ($bundleName) {
            case '2D Game':      $name .= 'game2d.png';     break;
            case 'Hot Key':      $name .= 'hotkey.png';     break;
            case 'HTTP Client':  $name .= 'httpClient.png'; break;
            case 'Material UI':  $name .= 'jfoenix.png';    break;
            case 'JSoup':        $name .= 'jsoup.png';      break;
            case 'Mailer':       $name .= 'mail.png';       break;
            case 'FireBird SQL': $name .= 'firebird.png';   break;
            case 'MySQL':        $name .= 'mysql.png';      break;
            case 'PostgreSQL':   $name .= 'pgsql.png';      break;
            case 'SQLite':       $name .= 'sqlite.png';     break;
            case 'System Tray':  $name .= 'systemTray.png'; break;
            case 'ZIP':          $name .= 'zip.png';        break;
            
            default: return $this->getBundleIcon($bundleName);
        }
        
        return new UXImage($name, 16, 16);
    }
    
    /**
     * Иконки пользовательских пакетов, елси они были устанволенны в студии
     */
    public function getBundleIcon ($bundleName) {
        $extensions = [];
        $path = System::getProperty('user.home') . self::DEFAULT_DEPENDENCY_PATH;
        
        foreach (fs::scan($path, ["extensions" => ["jar"], "namePattern" => '^dn-(.*?)\.jar$']) as $jar) {
            if (str::contains($jar, $bundleName)) {
                $zip = new ZipFile($jar);
                $image = "";
            
                foreach ($zip->statAll() as $key => $stat) {
                
                    if (str::startsWith($key, self::DEPENDENCY_ICON_PATH . $bundlename)) {
                        if ($stat["crc"] != 0){
                            $zip->read($stat["name"], function ($stat, MiscStream $stream) use ($lab, &$image) {
                                $image = new UXImage($stream);
                            });
                            
                            return $image;
                        }
                    }
                }
            }
        }
        
        return false;
    }
}