<?php
namespace app\forms;

use Error;
use php\gui\UXTitledPaneWrapper;
use Exception;
use php\compress\ZipFile;
use std, gui, framework, app;


class MainForm extends AbstractForm
{

    /**
     * @var FSTreeProvider
     */
    public $fsTree;
    
    /**
     * @var UXSplitPane
     */
    private $split;
    
    /**
     * Путь до директории с проектами
     */
    public $projectDir;
    
    private $lastFileSelected;
    
    /**
     * @var LoggerReporter
     */
    public $logger;
    
    /**
     * @var Fileinfo
     */
    public $fileInfoPanel;
    
    
    use OpertaionTrait;


    /**
     * @event construct 
     */
    function doConstruct(UXEvent $e = null)
    {    
        $this->logger = new LoggerReporter();

        $this->firstRunReport();
        $this->formSizeSaver($this->ini);
        
        $this->width = $this->ini->get("width") ?? $this->width;
        $this->height = $this->ini->get("height") ?? $this->height;
        
        $this->tree->root = new UXTreeItem();
        $this->tree->rootVisible = false;
        
        $this->fsTree = new FSTreeProvider($this->tree->root);
        
        $this->add(new MainMenu()->getNode());
        
        $this->projectDir = $this->ini->get('ProjectDirectory');
        
        $this->leftContainer->content->add(new SelectDirectoryCombobox()->getNode());
        
        try {
            $treeEvents = new FSTreeEvents();
            $this->fsTree->onFileSystem([$treeEvents, 'onFileSystem']);
            $this->fsTree->onZipFileSystem([$treeEvents, 'onZipFileSystem']);
        } catch (Exception $ex) {
            $this->errorAlert($ex);
        }
        

        $this->makeSplitter();
        $this->add($this->split);
        UXSplitPane::setResizeWithParent($this->leftContainer, false);

        
        // фикс мигания экрана если окно развернуто на весь экран и выбрана не светлая тема
        waitAsync(100, function () {
            if ($this->ini->get("maximized") == 1) {
                $this->maximized = true;
            }
            
            $this->split->setDividerPosition(0, $this->ini->get("splitter") ?: 0.25);
            
            $this->opacity = 1;
            $this->centerOnScreen();
        });
        
        $this->fileInfoPanel = new Fileinfo();
        $this->add($this->fileInfoPanel->getNode());   
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
        static $context = new FileContextMenu(),
            $contextRoot = new DirectoryContextMenu();
        
        if ($this->fsTree->getFileByNode($this->tree->focusedItem) === false) {
            // чтобы контекстное меню не появлялось на директориях в архиве
            if ($this->tree->focusedItem->children->count() == 0) {
                $context->showByNode($e);
            }
            return;
        }
        
        $contextRoot->showByNode($e);
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
     * @event close 
     */
    function doClose(UXWindowEvent $e = null)
    {    
        $this->ini->set("splitter", $this->split->dividerPositions);
    }

    /**
     * @event show 
     */
    function doShow(UXWindowEvent $e = null)
    {    
        // задержка у браузера перед отрисовкой страницы слишком долгая, по этому таймер в 0.5 скунду чтобы не мелькало
        waitAsync(500, function () {
            $this->browser->show();
        });
        
        $this->showCodeInBrowser('', 'html');
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
        $this->showMeta(["size" => $provider->size($path)]);
        
        $this->fileInfoPanel->updateCreatedAt($provider->createdAt($path));
        $this->fileInfoPanel->updateModifiedAt($provider->modifiedAt($path));
    }
    
    
    public function showCodeInBrowser ($output, $ext = 'config') {
        $output = str_replace(['<', '>'], ['&lt;', '&gt;'], $output); 
        $this->browser->engine->loadContent(
            str_replace(['${lang}', '${code}'], [$ext, $output], Stream::of('res://.data/web/highlight.html'))
        );
    }
    
    
    public function showMeta ($meta) {
        $meta = $meta["size"];
        $types = [Localization::get('ui.sidepanel.fileSizeFromat.b'), Localization::get('ui.sidepanel.fileSizeFromat.kb'), Localization::get('ui.sidepanel.fileSizeFromat.mb'), Localization::get('ui.sidepanel.fileSizeFromat.gb')];
        
        $index = floor(str::length($meta) / 3);
        
        $this->fileInfoPanel->updateFileSize(round($meta / pow(1024, $index), 2) . ' ' . $types[$index]);
    }
    
    private function makeSplitter ()
    {
        $this->split = new UXSplitPane([$this->leftContainer, $this->rightContainer]);
        $this->split->position = [0, 0];
        $this->split->topAnchor = 25;
        $this->split->bottomAnchor = true;
        $this->split->rightAnchor = true;
        $this->split->leftAnchor = true;
    }
    
}
