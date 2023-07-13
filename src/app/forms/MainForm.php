<?php
namespace app\forms;

use php\gui\UXTitledPaneWrapper;
use bundle\windows\Registry;
use Exception;
use php\compress\ZipFile;
use std, gui, framework, app;


class MainForm extends AbstractForm
{

    const REGISTRY_PATH = 'HKCU\SOFTWARE\ProjectView';
    
    
    /**
     * @var FSTreeProvider
     */
    public $fsTree;
    
    /**
     * Путь до директории с проектами
     */
    public $projectDir;
    
    private $lastFileSelected;
    
    /**
     * @var Registry
     */
    public $reg;
    
    /**
     * @var LoggerReporter
     */
    public $logger;
    
    /**
     * @var MainMenuEvents
     */
    public $mainMenuEvents;


    /**
     * @event construct 
     */
    function doConstruct(UXEvent $e = null)
    {    
        $this->logger = new LoggerReporter();
        $this->mainMenuEvents = new MainMenuEvents();

        try {
            $this->reg = Registry::of(self::REGISTRY_PATH);
            
            if (($path = $this->reg->read('ProjectDirectory')) !== null) {
                $this->ini->set("ProjectDirectory", $path);
                $this->reg->clear(); // удаляем старые записи в реестре т.к. теперь сохраняем настрйоки в ini
            }
        } catch (Exception $ignore) {}
        
        $this->firstRunReport();
        $this->formSizeSaver($this->ini);
        
        if ($this->ini->get("maximized") == 1) {
            $this->maximized = true;
        } else {
            $this->width = $this->ini->get("width");
            $this->height = $this->ini->get("height");
        }
        
        // ----------------------------------------
        
        $this->tree->root = new UXTreeItem();
        $this->tree->rootVisible = false;
        
        $this->fsTree = new FSTreeProvider($this->tree->root);
        
        $bar = new UXMenuBar();
        $bar->classes->add("menu-bar");
        $bar->leftAnchor = $bar->rightAnchor = 0;
        
        
        ContextMenuHelper::of($bar)->addCategory(Localization::get('ui.mainMenu.selectDirectory'), [$this->mainMenuEvents, 'selectedFolder']);
        
        $themeCategory = ContextMenuHelper::of($bar)->addCategory(Localization::get('ui.mainMenu.theme'));
        
        $themeList = [
            "light" => Localization::get('ui.mainMenu.theme.light'),
            "dark" => Localization::get('ui.mainMenu.theme.dark'),
            "nord" => Localization::get('ui.mainMenu.theme.nord')
        ];
        
        foreach ($themeList as $theme => $text) {
            $themeCategory->addItem(null, function ($ev) use ($themeCategory, $themeList) {
                $this->mainMenuEvents->changeTheme($ev, $themeCategory->getTarget(), $themeList);
            }, $node = new UXCheckbox($text));
            
            if ($theme === $this->data('theme')) {
                $node->selected = true;
                $node->enabled = false;
                $this->addStylesheet('.theme/' . $theme. '.theme.fx.css');
            }
        }
        
        ContextMenuHelper::of($bar)->addCategory(Localization::get('ui.mainMenu.about'), [$this->mainMenuEvents, 'about']);
        
        
        
        $this->add($bar);
        
        $this->infoPanelSwitcher->toFront();
        
        $this->projectDir = $this->ini->get('ProjectDirectory');
        
        try {
            $this->fsTree->onFileSystem(function (StandartFileSystem $provider, $path = null) {
                $this->filePath->text = $path;
                
                $this->updateFileInfoIcon($provider, $path);
                $this->updateFileinfo($provider, $path);
            });
            
            $this->fsTree->onZipFileSystem(function (ZipFileSystem $provider, $zipPath, $path) {
                $this->filePath->text = $zipPath;
                
                if (!$provider->getZipInstance()->has($zipPath)) {
                    $zipPath = str_replace('\\', '/', $zipPath);
                    
                    if (!$provider->getZipInstance()->has($zipPath)) {
                        $zipPath = str_replace('/', '\\', $zipPath);
                    }
                }
                
                $this->updateFileInfoIcon($provider, $zipPath);
                
                if ($provider->isFile($zipPath)) {
                    $provider->getZipInstance()->read($zipPath, function (array $stat, Stream $output) use ($zipPath) {
                        $this->showMeta($stat);
                        
                        $ext = $this->getHighlightType(fs::ext($zipPath));
                        
                        if (fs::ext($zipPath) === 'fxml') {
                            $output = (string) $output;
                            $this->_showForm($output, $this->image);
                            $this->tabPane->selectedIndex = 1;
                        } else if ($ext == 'image') {
                            $this->image->image = new UXImage($output);
                            $output = "Binary";
                            $this->tabPane->selectedIndex = 1;
                        } else {
                            switch (fs::ext($zipPath)) {
                                case 'zip':
                                case 'exe':
                                case 'jar':
                                case 'ttf':
                                    $output = "Binary";
                            }
                            
                            $this->tabPane->selectedIndex = 0;
                        }
        
                        $this->showCodeInBrowser($output, $ext);
                    });
                    
                    $this->updateFileinfo($provider, $zipPath);
                } else if ($provider->isDirectory($zipPath)) {
                    $this->fileSize->text = "unknown";
                }
            });
            
        } catch (Exception $ex) {
            $this->errorAlert($ex);
        }
        
        // задержка у браузера перед отрисовкой страницы слишком долгая, по этому таймер в 0.5 скунду чтобы не мелькало
        timer::after(500, function () { $this->browser->show(); });
        
        $this->showCodeInBrowser('', 'html');
        
        $this->centerOnScreen();
        
        $this->opacity = 1;
        $this->show();
    }
    
    
    /**
     * @event tree.click-Left 
     */
    function doTreeClickLeft(UXMouseEvent $e = null)
    {    
        if (count($this->tree->selectedIndexes) < 1) return;
        
        // чтобы не пререотрисовывать по новой данные если кликнули второй раз по элементу
        if ($this->lastFileSelected === $this->tree->focusedItem->value) {
            $zip = $this->fsTree->getZipByNode($this->tree->focusedItem);
        
            if ($zip instanceof ZipFileSystem) {
                // если были выбрные одинаковые файлы по имени но в разных источниках
                if ($this->lastFileSelectedProject === $zip->getZipInstance()->getPath()) {
                    return;
                }
                
                $this->lastFileSelectedProject = $zip->getZipInstance()->getPath();
            }
        }
        
        $this->lastFileSelected = $this->tree->focusedItem->value;
        
        $this->fsTree->getFileInfo($this->tree->focusedItem);
    }
    
    
    public function updateFileInfoIcon (AbstractFileSystem $provider, $path)
    {
        static $iconFileSelected;
        
        /**
         * IconFileSelected $iconFileSelected
         */
        if ($iconFileSelected == null) {
            $iconFileSelected = new IconFileSelected();
            $this->fileInfo->add($iconFileSelected->getNode());
        }
        
        if ($provider->isfile($path)) {
            
            $iconFileSelected->updateClasses(["file-icon"]);
            $iconFileSelected->setSize(52, 68);
            
            switch (fs::ext($path)) {
                case 'zip':
                    $iconFileSelected->updateClasses(["zip-icon"]);
                    $iconFileSelected->setSize(84, 64);
                    $iconFileSelected->updateText("");
                    break;
                case 'php':
                    $iconFileSelected->updateText("PHP");
                    break;
                case 'fxml':
                    $iconFileSelected->updateText("FXML");
                    break;
                default: $iconFileSelected->updateText("");
            }
        } else if ($provider->isDirectory($path)) {
            $iconFileSelected->updateClasses(["directory-icon"]);
            $iconFileSelected->setSize(84, 64);
            $iconFileSelected->updateText("");
        }
    }
    

    /**
     * @event browser.load 
     * @event browser.running 
     */
    function doBrowserRunning(UXEvent $e = null)
    {
        try {
            $e->sender->engine->addSimpleBridge('injections', function (string $text) {
                $alert = new UXAlert("ERROR");
                $alert->headerText = "";
                
                $alert->expanded = false;
                $alert->expandableContent = new UXScrollPane(new UXAnchorPane);
                $alert->expandableContent->height = 400;
                $alert->expandableContent->content->add(new UXLabel(var_export(func_get_args(), true)));
                
                $alert->title = Localization::get('message.browser.error');
                $alert->contentText = $text;
                $alert->show();
            });
            
            $e->sender->engine->executeScript(Stream::of('res://.data/web/run_prettify.js'));
            
            $theme = $this->data('theme') ?: 'light';

            $e->sender->engine->userStyleSheetLocation = new ResourceStream('/.data/web/' . $theme . '.css')->toExternalForm();
        } catch (Exception $ex) {
            $this->errorAlert($ex, true);
        }
    }


    /**
     * @event tree.click-Right 
     */
    function doTreeClickRight(UXMouseEvent $e = null)
    {    
        if (count($this->tree->selectedIndexes) < 1) return;
        
        static $context = new UXContextMenu(),
               $contextRoot = new UXContextMenu();
        
        $this->getConfig()->set(ContextMenuEvents::CONTEXT_MENU_X, $e->x);
        $this->getConfig()->set(ContextMenuEvents::CONTEXT_MENU_Y, $e->y);
        
        if ($context->items->isEmpty()) {
            $helper = ContextMenuHelper::of($context, $config = new Configuration());
            
            $config->set(ContextMenuHelper::GRAPHIC_WIDTH, 16);
            $config->set(ContextMenuHelper::GRAPHIC_HEIGHT, 16);
            
            $helper->addItem(Localization::get('ui.contextMenu.saveAs'), [ContextMenuEvents::getInstance($this), "saveAs"], $helper->makeIcon('res://.data/img/context-menu-icons/save.png'));
            $helper->addItem(Localization::get('ui.contextMenu.rename'), [ContextMenuEvents::getInstance($this), "rename"], $helper->makeIcon('res://.data/img/context-menu-icons/edit.png'));
            $helper->addItem(Localization::get('ui.contextMenu.delete'), [ContextMenuEvents::getInstance($this), "delete"], $helper->makeIcon('res://.data/img/context-menu-icons/delete.png'));
        }
        
        if (($fs = $this->fsTree->getFileByNode($this->tree->focusedItem)) === false) {
            // чтобы контексттоное меню не появлялось на директориях в архиве
            if ($this->tree->focusedItem->children->count() == 0) {
                $context->showByNode($e->sender, $e->x, $e->y);
            }
            
            return;
        }
        
        if ($contextRoot->items->isEmpty()) {
            $helper = ContextMenuHelper::of($contextRoot, $config = new Configuration());
            
            $config->set(ContextMenuHelper::GRAPHIC_WIDTH, 16);
            $config->set(ContextMenuHelper::GRAPHIC_HEIGHT, 16);
            
            $helper->addItem(Localization::get('ui.contextMenu.showInExplorer'), [ContextMenuEvents::getInstance($this), 'showInExplorer'], $helper->makeIcon('res://.data/img/context-menu-icons/open-folder.png'));
        }
        
        $contextRoot->showByNode($e->sender, $e->x, $e->y);
    }
    

    /**
     * @event infoPanelSwitcher.click-Left 
     */
    function doInfoPanelSwitcherClickLeft(UXMouseEvent $e = null)
    {    
        $padding = 8;
        
        if ($this->infoPanelSwitcher->selected) {
            $this->tabPane->rightAnchor = $this->fileInfo->width + $padding * 2;
            $this->fileInfo->rightAnchor = $padding;
            $this->ini->set('panel_file_information_show', 1);
        } else {
            $this->tabPane->rightAnchor = $padding;
            $this->fileInfo->rightAnchor -= $this->fileInfo->width + $padding;
            $this->ini->set('panel_file_information_show', 0);
        }
    }
    

    /**
     * @event infoPanelSwitcher.construct 
     */
    function doInfoPanelSwitcherConstruct(UXEvent $e = null)
    {    
        // remove empty hint
        UXTooltip::uninstall($e->sender, $e->sender->tooltip);
        try {
            if ($this->ini->get('panel_file_information_show') == 1) {
                $this->infoPanelSwitcher->selected = true;
                $this->doInfoPanelSwitcherClickLeft();
            }
        } catch (Exception $ignore) {}
    }
    

    /**
     * @event fileInfo.construct 
     */
    function doFileInfoConstruct(UXEvent $e = null)
    {    
        $e->sender->lookup('.panel-title')->topAnchor = -14;
        $this->fileInfo->title = Localization::get('ui.sidepanel.fielInfo.title');
        $this->createdAtLabel->text = Localization::get('ui.sidepanel.fielInfo.createdAt');
        $this->modifiedAtLabel->text = Localization::get('ui.sidepanel.fielInfo.modifiedAt');
        $this->fileSizeLabel->text = Localization::get('ui.sidepanel.fielInfo.fileSize');
    }
    

    /**
     * @event tabPane.construct 
     */
    function doTabPaneConstruct(UXEvent $e = null)
    {    
        $this->tabPane->tabs[0]->text = Localization::get('ui.tab.viewCode');
        $this->tabPane->tabs[0]->graphic = new UXHBox();
        $this->tabPane->tabs[0]->graphic->classes->add("view-tab-icon");
        
        $this->tabPane->tabs[1]->text = Localization::get('ui.tab.viewView');
        $this->tabPane->tabs[1]->graphic = new UXHBox();
        $this->tabPane->tabs[1]->graphic->classes->add("code-tab-icon");
    }

    /**
     * @event combobox.construct 
     */
    function doComboboxConstruct(UXEvent $e = null)
    {    
        $arrow = $e->sender->lookup('.arrow');
        $arrow->rotate = 90;
        
        $e->sender->observer('showing')->addListener(function ($old, $new) use ($arrow) {
            $speed = 1000;
            $minangle = 90;
            $maxangle = 270;
            $timer = new UXAnimationTimer(function () use (&$timer, $arrow, $new, $speed, $minangle, $maxangle) {
                if ($new) {
                    $arrow->rotate += $speed * UXAnimationTimer::FRAME_INTERVAL;
                } else {
                    $arrow->rotate -= $speed * UXAnimationTimer::FRAME_INTERVAL;
                }
                
                if ($arrow->rotate % $maxangle == 0 || $arrow->rotate <= $minangle) {
                    $timer->stop();
                }
                
                if ($arrow->rotate > $maxangle) $arrow->rotate = $maxangle;
            });
            $timer->start();
        });
        
        if (count($this->ini->get('directoryList')) == 0 && $this->projectDir != null) {
            $this->ini->set("directoryList", [$this->projectDir]);
        }
        
        if (is_array($this->ini->get('directoryList'))) {
            foreach ($this->ini->get('directoryList') as $key => $path) {
                $this->combobox->items->add([$path, '.directory-icon']);
                if ($path == $this->projectDir) {
                    $this->tree->root->children->clear();
                    $this->combobox->selectedIndex = $key;
                    $this->fsTree->setDirectory($path);
                }
            }
        } else {
            if ($this->ini->get('directoryList') != null) {
                $this->combobox->items->add([$this->ini->get('directoryList'), '.directory-icon']);
                $this->combobox->selectedIndex = 0;
                $this->fsTree->setDirectory($this->ini->get('directoryList'));
            }
        }
        
        $e->sender->observer('value')->addListener(function ($old, $new) {
            $this->tree->root->children->clear();
            $this->ini->set('ProjectDirectory', $new[0]);
            $this->fsTree->setDirectory($new[0]);
        });
        
        $cellFactory = function (UXListCell $cell, $node, bool $selected = null) {
            $item = new DirectoryItem();
            $item->setText($node[0]);
            $item->setImage('directory-icon');

            $cell->text = "";
            $cell->graphic = $item->getNode();
        }; 
        
        $this->combobox->onCellRender($cellFactory);
        
        $this->combobox->onButtonRender(function (UXListCell $cell, $node) use () {
            $item = new SelectedDirectoryItem();
            $item->setText($node[0], $this->combobox->width);
            $item->setTitle(Localization::get("ui.directorySwitcher.activeDirectory"));
            $item->setImage('directory-icon');

            $cell->graphic = $item->getNode();
        });
    }

    
    public function getHighlightType ($zipPath) {
        switch ($zipPath) {
            case 'axml':
            case 'fxml': $ext = 'xml'; break;
            case 'php': $ext = 'php'; break;
            case 'css': $ext = 'css'; break;
            case 'ico':
            case 'bmp':
            case 'png':
            case 'jpg':
            case 'jpeg':$ext = 'image'; break;
            case 'zip': $ext = 'zip'; break;
            case 'exe': $ext = 'exe'; break;
            
            default:    $ext = 'config';
        }
        
        return $ext;
    }
    
    
    public function updateFileinfo ($provider, $path) {
        $this->createdAt->text = $provider->createdAt($path);
        $this->modifiedAt->text = $provider->modifiedAt($path);
        $this->showMeta(["size" => $provider->size($path)]);
    }
    
    
    private function showCodeInBrowser ($output, $ext = 'config') {
        $output = str_replace(['<', '>'], ['&lt;', '&gt;'], $output); 
        $this->browser->engine->loadContent(
            str_replace(['${lang}', '${code}'], [$ext, $output], Stream::of('res://.data/web/highlight.html'))
        );
    }
    
    
    private function showMeta ($meta) {
        $meta = $meta["size"];
        $types = [Localization::get('ui.sidepanel.fileSizeFromat.b'), Localization::get('ui.sidepanel.fileSizeFromat.kb'), Localization::get('ui.sidepanel.fileSizeFromat.mb'), Localization::get('ui.sidepanel.fileSizeFromat.gb')];
        
        $index = floor(str::length($meta) / 3);
        
        $this->fileSize->text = round($meta / pow(1024, $index), 2) . ' ' . $types[$index];
    }
}
