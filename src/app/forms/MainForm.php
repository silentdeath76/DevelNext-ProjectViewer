<?php
namespace app\forms;

use bundle\windows\Registry;
use Exception;
use php\compress\ZipFile;
use std, gui, framework, app;


class MainForm extends AbstractForm
{
    
    const TREE_ROOT_ELEMENT_TITLE = 'Projects';
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
     * @event construct 
     */
    function doConstruct(UXEvent $e = null)
    {    
        $reg = Registry::of(self::REGISTRY_PATH);
        
        // сохраняем состояния окна в реестр
        $this->formSizeSaver($reg);
        
        try {
            if ($reg->read("maximized")->getValue() == '0x1') {
                $this->maximized = true;
            } else {
                $this->width = $reg->read("width")->getValue();
                $this->height = $reg->read("height")->getValue();
            }
        } catch (Exception $ignore) {}
        
        // ----------------------------------------
        
        $this->tree->root = new UXTreeItem(MainForm::TREE_ROOT_ELEMENT_TITLE);
        $this->tree->root->expanded = true;
        
        $this->fsTree = new FSTreeProvider($this->tree->root);
        
        $bar = new UXMenuBar();
        $bar->classes->add("menu-bar");
        $bar->leftAnchor = $bar->rightAnchor = 0;
        
        $bar->menus->add($menu = new UXMenu());
        $menu->graphic = new UXLabel("Выбрать директорию");
        $menu->graphic->padding = 1;
        $menu->graphic->on("click", function () use ($reg) {
            $dc = new UXDirectoryChooser();
            
            if (($path = $dc->showDialog($this)) == null) return;
            
            $this->projectDir = $path;
            
            try {
                $reg->add('ProjectDirectory', $this->projectDir);
                // $reg->add('author', 'https://vk.com/silentrs');
            } catch (Exception $ex) {
                $this->errorAlert($ex);
            }
            
            $this->tree->root->children->clear();
            
            try {
                $this->fsTree->setDirectory($this->projectDir);
            } catch (Exception $ex) {
                $this->errorAlert($ex);
            }
        });
        
        $this->add($bar);
        
        $this->toggleButton->toFront();
        
        try {
            $this->projectDir = $reg->read('ProjectDirectory');
        } catch (Exception $ignore) { }
        
        $this->tree->fixedCellSize = 28;
        $this->tree->rootVisible = false;
        
        // if ($this->projectDir != null) {
            try {
                $this->fsTree->onFileSystem(function (StandartFileSystem $provider, $path = null) {
                    $this->filePath->text = $path;
                    
                    if ($provider->isFile($path)) {
                        $this->fileImage->image = new UXImage('res://.data/img/ui/archive-60.png');
                    } else if ($provider->isDirectory($path)) {
                        $this->fileImage->image = new UXImage('res://.data/img/ui/folder-60.png');
                    } else {
                        Logger::warn('WTF?');
                    }
                    
                    $this->createdAt->text = $provider->createdAt($path);
                    $this->modifiedAt->text = $provider->modifiedAt($path);
                    $this->showMeta(["size" => filesize($path)]);
                });
                
                $this->fsTree->onZipFileSystem(function (ZipFileSystem $provider, $zipPath, $path) {
                    $this->filePath->text = $zipPath;
                    
                    if (!$provider->getZipInstance()->has($zipPath)) {
                        $zipPath = str_replace('\\', '/', $zipPath);
                        
                        if (!$provider->getZipInstance()->has($zipPath)) {
                            $zipPath = str_replace('/', '\\', $zipPath);
                            
                            if (!$provider->getZipInstance()->has($zipPath)) {
                                if ($provider->isDirectory($zipPath)) {
                                    // Logger::info('Selected directory ' . $focusedItem);
                                }
                            }
                        }
                    }
                    
                    if ($provider->isFile($zipPath)) {
                        $this->fileImage->image = new UXImage('res://.data/img/ui/file-60.png');
                        
                        $provider->getZipInstance()->read($zipPath, function (array $stat, Stream $output) use ($zipPath) {
                            $this->showMeta($stat);
                            switch (fs::ext($zipPath)) {
                                case 'axml':
                                case 'fxml': {
                                    $ext = 'xml';
                                    $output = (string) $output;
                                    if (fs::ext($this->tree->focusedItem->value) === 'fxml') {
                                        $this->_showForm($output, $this->image);
                                    }
                                    
                                    break;
                                }
                                case 'php': $ext = 'php'; break;
                                case 'css': $ext = 'css'; break;
                                case 'ico':
                                case 'png':
                                case 'jpg':
                                case 'jpeg':$ext = 'image'; break;
                                case 'zip': $ext = 'zip'; break;
                                
                                default:    $ext = 'config';
                            }
                            
                            if ($ext == 'image') {
                                $this->image->image = new UXImage($output);
                                $output = "Binary";
                            }
                            
                            if ($ext == 'zip') {
                                $output = "Binary";
                            }
                            
                            /* $output = str_replace(['<', '>'], ['&lt;', '&gt;'], $output); 
                            
                            $this->browser->engine->loadContent(
                                str_replace(['${lang}', '${code}'], [$ext, $output], Stream::of('res://.data/web/highlight.html'))
                            ); */
                            
                            
                            $this->showCodeInBrowser($output, $ext);
                            
                            /* try {
                                $this->browser->engine->executeScript(Stream::of('res://.data/web/run_prettify.js'));
                            } catch (Exception $ex) {
                                $this->errorAlert($ex, true);
                            } */
                        });
                    } else if ($provider->isDirectory($zipPath)) {
                        $this->fileImage->image = new UXImage('res://.data/img/ui/folder-60.png');
                    }
                    
                    $this->createdAt->text = $provider->createdAt($zipPath);
                    $this->modifiedAt->text = $provider->modifiedAt($zipPath);
                });
                
                $this->fsTree->setDirectory($this->projectDir);
            } catch (Exception $ex) {
                $this->errorAlert($ex);
            }
        // }
        
        // задержка у браузера перед отрисовкой страницы слишком долгая, по этому таймер в 1 скунду чтобы не мелькало
        timer::after(1000, function () { $this->browser->show(); });
        
        /* $this->browser->engine->loadContent(str_replace(['${lang}', '${code}'], [
            'html', ''
        ], Stream::of('res://.data/web/highlight.html'))); */
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
        if ($this->tree->focusedItem->value === self::TREE_ROOT_ELEMENT_TITLE) return;
        
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
    



    /**
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
                
                $alert->title = 'JS Handler';
                $alert->contentText = $text;
                $alert->show();
            });
            
            $e->sender->engine->executeScript(Stream::of('res://.data/web/run_prettify.js'));
            
            // $e->sender->engine->userStyleSheetLocation = new ResourceStream('/.data/web/prettify.css')->toExternalForm();
            $e->sender->engine->userStyleSheetLocation = new ResourceStream('/.data/web/style.css')->toExternalForm();
        } catch (Exception $ex) {
            $this->errorAlert($ex, true);
        }
    }

    /**
     * @event tree.click-Right 
     */
    function doTreeClickRight(UXMouseEvent $e = null)
    {    
        if ($this->tree->focusedItem->value === self::TREE_ROOT_ELEMENT_TITLE) return;
        
        static $context = new UXContextMenu(),
               $contextRoot = new UXContextMenu();
        
        $this->getConfig()->set(ContextMenuEvents::CONTEXT_MENU_X, $e->x);
        $this->getConfig()->set(ContextMenuEvents::CONTEXT_MENU_Y, $e->y);
        
        if ($context->items->isEmpty()) {
            $helper = ContextMenuHelper::of($context, $config = new Configuration());
            
            $config->set(ContextMenuHelper::GRAPHIC_WIDTH, 16);
            $config->set(ContextMenuHelper::GRAPHIC_HEIGHT, 16);
            
            $helper->addItem("Сохранить как", [ContextMenuEvents::getInstance($this), "saveAs"], $helper->makeIcon('res://.data/img/context-menu-icons/save.png'));
            $helper->addItem("Переименовать", [ContextMenuEvents::getInstance($this), "rename"], $helper->makeIcon('res://.data/img/context-menu-icons/edit.png'));
            $helper->addItem("Удалить", [ContextMenuEvents::getInstance($this), "delete"], $helper->makeIcon('res://.data/img/context-menu-icons/delete.png'));
            // $helper->addItem("Информация о файле", [ContextMenuEvents::getInstance($this), "fullInfo"], $helper->makeIcon('res://.data/img/context-menu-icons/info.png'));
        }
        
        if (($fs = $this->fsTree->getFileByNode($this->tree->focusedItem)) === false) {
            // чтобы контексттоное меню не появлялось на директориях
            if ($this->tree->focusedItem->children->count() == 0) {
                $context->showByNode($e->sender, $e->x, $e->y);
            }
            return;
        }
        
        if ($contextRoot->items->isEmpty()) {
            $helper = ContextMenuHelper::of($contextRoot, $config = new Configuration());
            
            $config->set(ContextMenuHelper::GRAPHIC_WIDTH, 16);
            $config->set(ContextMenuHelper::GRAPHIC_HEIGHT, 16);
            
            $helper->addItem("Показать в проводнике", [ContextMenuEvents::getInstance($this), 'showInExplorer'], $helper->makeIcon('res://.data/img/context-menu-icons/open-folder.png'));
        }
        
        $contextRoot->showByNode($e->sender, $e->x, $e->y);
    }

    /**
     * @event toggleButton.click-Left 
     */
    function doToggleButtonClickLeft(UXMouseEvent $e = null)
    {    
        $reg = Registry::of(self::REGISTRY_PATH);
        
        if ($this->toggleButton->selected) {
            $this->tabPane->rightAnchor = $this->fileInfo->width + 16;
            $this->fileInfo->rightAnchor = 8;
            $reg->add('panel_file_information_show', 1);
        } else {
            $this->tabPane->rightAnchor = 8;
            $this->fileInfo->rightAnchor -= $this->fileInfo->width + 8;
            $reg->add('panel_file_information_show', 0);
        }
    }

    /**
     * @event toggleButton.construct 
     */
    function doToggleButtonConstruct(UXEvent $e = null)
    {    
        $reg = Registry::of(self::REGISTRY_PATH);
        
        try {
            if ($reg->read('panel_file_information_show')->getValue() == 1) {
                $this->toggleButton->selected = true;
                $this->doToggleButtonClickLeft();
            }
        } catch (Exception $ignore) {}
    }

    /**
     * @event fileInfo.construct 
     */
    function doFileInfoConstruct(UXEvent $e = null)
    {    
        $e->sender->lookup('.panel-title')->topAnchor = -14;
    }

    /**
     * @event browser.load 
     */
    function doBrowserLoad(UXEvent $e = null)
    {    
        $this->doBrowserRunning($e);
    }

    
    private function showCodeInBrowser ($output, $ext = 'config') {
        $output = str_replace(['<', '>'], ['&lt;', '&gt;'], $output); 
        $this->browser->engine->loadContent(
            str_replace(['${lang}', '${code}'], [$ext, $output], Stream::of('res://.data/web/highlight.html'))
        );
    }
    
    private function showMeta ($meta) {
        $meta = $meta["size"];
        $types = ['b', 'kb', 'mb', 'gb'];
        
        $index = floor(str::length($meta) / 3);
        
        $this->fileSize->text = round($meta / pow(1024, $index), 2) . $types[$index];
    }
    
    
    public function errorAlert (Exception $ex, $detailed = false) {
        $alert = new UXAlert("ERROR");
        $alert->headerText = "";
        
        if ($detailed) {
            $alert->expanded = false;
            $alert->expandableContent = new UXScrollPane(new UXAnchorPane);
            $alert->expandableContent->height = 400;
            $alert->expandableContent->content->add(new UXLabel(var_export($ex->getTraceAsString(), true)));
        }
        
        $alert->title = 'Произошла ошибка';
        $alert->contentText = $ex->getMessage();
        $alert->show();
    }
    
    public function _showForm ($formData, $outputImage) {
        $n = new Environment();
        $n->importAutoLoaders();
        
        // несовсем понимаю почему требуется импорт именно класса MainForm, причем не важно какое имя у загружаемой формы
        $n->importClass(MainForm::class);
        $n->importClass('php\gui\framework\AbstractForm');
        $n->importClass('php\gui\framework\AbstractModule');
        $n->execute(function () use ($formData, $outputImage) {
            $layout = new UXLoader()->loadFromString($formData);
            $form = new UXForm();
            $form->add($layout);
            $outputImage->image = $form->layout->snapshot();
        });
    }
    
}
