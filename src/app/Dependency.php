<?php
namespace app;

use app;
use Exception;
use framework;
use gui;
use std;
use php\compress\ZipFile;

class Dependency 
{
    const DEFAULT_DEPENDENCY_PATH = '\DevelNextLibrary\bundles\\';
    const DEPENDENCY_ICON_PATH = '.data/img/develnext/bundle/';
    
    
    private $linkIconWidth = 10;
    private $linkIconHeight = 10;
    
    private $iconWidth = 16;
    private $iconHeight = 16;
    
    /**
     * @var ObjectStorage
     */
    private $imageCache;
    
    
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
        
        
        $sort = [];
        $pWidth = app()->form("MainForm")->container->width - 10; // 10 - padding 5px

        foreach ($panels as $panel) {
            $panelWidth = $this->getPanelWidth($panel);
            
            if ($panel->children->count() > 2) {
                $panelWidth += 42; // 3 * 2 + 5 * 2 + $this->iconWidth + $this->linkIconWidth
            } else {
                $panelWidth += 27; // 3 * 2 + 5 + $this->iconHeight
            }
            
            if (count($sort) === 0) {
                $sort[] = [
                    [
                        "width" => $panelWidth,
                        "panel" => $panel
                    ]
                ];
            } else {
                foreach ($sort as $key => $sortPanels) {
                    if (count($sortPanels) === 1) {
                        if (($sortPanels[0]["width"] + $panelWidth) < $pWidth) {
                            $sort[$key][] = [
                                "width" => $panelWidth,
                                "panel" => $panel
                            ];
                            continue 2;
                        } 
                    } else {
                        $sWidth = 0;
                        foreach ($sortPanels as $s_panel) {
                            $sWidth += $s_panel["width"];
                        }
                        
                        if (($sWidth + $panelWidth) < $pWidth) {
                            $sort[$key][] = [
                                "width" => $panelWidth,
                                "panel" => $panel
                            ];
                            continue 2;
                        }
                    }
                }
                
                $sort[] = [
                    [
                        "width" => $panelWidth,
                        "panel" => $panel
                    ]
                ];
            }
        }
        
        foreach ($sort as $l) {
            foreach ($l as $panel) {
                try {
                    app()->form("MainForm")->flowPane->children->add($panel["panel"]);
                } catch (Exception $ignore) {}
            }
        }
    }
    
    
    private function getPanelWidth ($panel) {
        return $panel->children->offsetGet(1)->font->calculateTextWidth($panel->children->offsetGet(1)->text) + 16;
    }

    
    public function makeUi ($image, $name) {
        if (!($image instanceof UXImage)) {
            $image = new UXImage('res://.data/img/ui/image-16.png');
        }
        
        $panel = new UXHBox();
        $panel->padding = 3;
        $panel->spacing = 5;
        
        $panel->add($view = new UXImageView($image));
        $view->width = $this->iconWidth;
        $view->height = $this->iconHeight;
        
        $panel->add($label = new UXLabelEx($name));
        $label->autoSize = true;
        $label->autoSizeType = 'HORIZONTAL';
        $panel->classes->add('DependencyItem');
        
        if (($url = $this->getLink($name)) != false) {
            $panel->add($link = new UXHBox);
            $link->classes->addAll(["link", "open-link-icon"]);
            $link->cursor = 'HAND';
            $link->maxWidth = $this->linkIconWidth;
            $link->maxHeight = $this->linkIconHeight;
            $link->minWidth = $this->linkIconWidth;
            $link->minHeight = $this->linkIconHeight;
            $tooltip = UXTooltip::of(Localization::get('message.link.openInBrowser'));
            UXTooltip::install($link, $tooltip);
            
            $link->on("click", function () use ($url) {
                if (uiConfirm(Localization::get('message.link.openInBrowser') . '?')) {
                    open($url);
                }
            });
        }
        
        return $panel;
    }
    
    
    private function getLink($name) {
        static $json = json_decode(FileStream::of('res://.data/dependencys.json'), true);
        
        $found = null;
        
        // если пает будет перименован то можно будет добавить алиас имени (как пример пакет windows: версия 1.2 - windows, версия 2.2 - Windows)
        // можно конечно сделать strtolower, но если пакет будет перименован по другому то все равно приедся делать алиасы
        Flow::of($json)->each(function ($array, $index) use ($name, &$found) {
            if (is_array($array["name"])) {
                if (in_array($name, $array["name"], true)) {
                    $found = [
                        "name" => $array["name"][0],
                        "link" => $array["link"]
                    ];
                    return false;
                }
            } else if ($array["name"] == $name) {
                $found = $array;
                return false;
            }
        });
        
        if (is_null($found)) {
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
            
            Logger::info("Unknown dependency: " . $name);
            
            return false;
        }
        
        return $found["link"];
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
        
        return new UXImage($name);
    }
    
    
    /**
     * Иконки пользовательских пакетов, елси они были установленны в студии
     */
    public function getBundleIcon ($bundleName) {
    
        if (!($this->imageCache instanceof ObjectStorage)) {
            $this->imageCache = new ObjectStorage();
        }
        
        if ($this->imageCache->exists($bundleName)) {
            return $this->imageCache->get($bundleName);
        }
        
        $path = System::getProperty('user.home') . self::DEFAULT_DEPENDENCY_PATH;
        
        foreach (fs::scan($path, ["extensions" => ["jar"], "namePattern" => '^dn-(.*?)\.jar$']) as $jar) {
            if (str::contains($jar, $bundleName)) {
                $zip = new ZipFile($jar);
                $image = null;
            
                foreach ($zip->statAll() as $key => $stat) {
                
                    if (str::startsWith($key, self::DEPENDENCY_ICON_PATH . $bundlename)) {
                        if ($stat["crc"] != 0){
                            $zip->read($stat["name"], function ($stat, MiscStream $stream) use (&$image) {
                                $image = new UXImage($stream);
                            });
                            
                            $this->imageCache->set($bundleName, $image);
                            
                            return $image;
                        }
                    }
                }
            }
        }
        
        return false;
    }
}