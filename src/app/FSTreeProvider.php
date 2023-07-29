<?php
namespace app;

use Exception;
use framework;
use php\compress\ZipFile;
use std;
use gui;
use app;

class FSTreeProvider implements IEvents
{
    const EMPTY_PATH_ELEMENT = '/path/';
    
    /**
     * @var Dependency
     */
    private $dependency;
    
    /**
     * @var ObjectStorage
     */
    private $imageCache;
    
    /**
     * @var UXTreeItem
     */
    private $rootItem;
    
    /**
     * @var TreeHelper
     */
    public $treeHelper;
    
    private $lastSelectedProject;
    
    private $selectedDirectory;


    public function __construct (UXTreeItem $rootTreeItem) {
        $this->rootItem = $rootTreeItem;
        $this->dependency = new Dependency();
        $this->imageCache = new ObjectStorage();
        $this->treeHelper = new TreeHelper();
    }
    
    
    public function setDirectory ($path) {
        $this->selectedDirectory = $path;
        
        fs::scan($path, ['extensions' => ['zip'], 'callback' => function (File $file) {
            $filePath = str::sub($file->getAbsoluteFile(), strlen($this->selectedDirectory) + 1);
            $this->zipFiles[$file->getAbsoluteFile()] = new ZipFile($file);
            $items = explode(File::DIRECTORY_SEPARATOR, $filePath);
            
            if ($file->isFile()) {
                $this->treeHelper->makeTree($this->rootItem, $items, function ($node, bool $isDir) use ($filePath) {
                    $this->applyIcon($node, ($isDir) ? FSTreeProvider::EMPTY_PATH_ELEMENT : $filePath);
                });
            }
        }]);
        
        $this->treeHelper->sort($this->rootItem);
    }
    
    
    /**
     * @return StandartFileSystem
     */
    public function getFileByNode (UXTreeItem $item) {
        $fs = new StandartFileSystem();
        $filePath = $this->selectedDirectory . $fs->getAbsolutePath($item);
        $filePath = MainModule::replaceSeparator($filePath);
        
        if ($fs->exists($filePath)) {
            return $fs;
        }
        
        return false;
    }
    
    
    /**
     * @return ZipFileSystem
     */
    public function getZipByNode (UXTreeItem $item) {
        $fs = new StandartFileSystem();
        $filePath = $this->selectedDirectory . $fs->getAbsolutePath($item);
        $filePath = MainModule::replaceSeparator($filePath);
        
        if (!$fs->isFile($filePath)) {
            if (!$fs->isDirectory($filePath)) {
                list($fsPath, $zipPath) = $this->getPaths($filePath);
                
                return $this->zipFiles[$fsPath];
            }
        }
        
        return false;
    }
    
    
    public function getFileInfo (UXTreeItem $item) {
        $fs = new StandartFileSystem();
        $filePath = $this->selectedDirectory . $fs->getAbsolutePath($item);
        $filePath = MainModule::replaceSeparator($filePath);
        
        // если выбранный елемент является файлом на диске
        if ($fs->isFile($filePath)) {
            if (!array_key_exists($filePath, $this->zipFiles)) {
                $this->zipFiles[$filePath] = new ZipFileSystem();
                
                $zipFile = new ZipFile($filePath);
                
                $this->zipFiles[$filePath]->setZipInstance($zipFile);
                
                // отрисовываем структуру выбранной директории в дереве
                $this->createTreeItemOfZip($item, $zipFile->statAll());
                
                // сортируем по алфавиту и чтобы сначала шли директории
                $this->treeHelper->sort($item);
            }
            
            call_user_func_array($this->events["onFileSystem"], [$fs, $filePath]);
        } else if ($fs->isDirectory($filePath)) {
            call_user_func_array($this->events["onFileSystem"], [$fs, $filePath]);
        } else { // если выбранный елемент является файлом в zip архиве
            list($fsPath, $zipPath) = $this->getPaths($filePath);
            try {
                if (!($this->zipFiles[$fsPath] instanceof ZipFileSystem)) {
                    $message = sprintf("Instance: %s ZipPath: %s FsPath: %s", get_class($this->zipFiles[$fsPath]) ?: "null", $zipPath, $fsPath);
                    app()->form("MainForm")->logger->console("Error is incorrect instance type", LoggerReporter::ERROR)->show();
                    app()->form("MainForm")->logger->discord($message, LoggerReporter::WARNING)->send();
                    return;
                }
                
                call_user_func_array($this->events["onZipFileSystem"], [$this->zipFiles[$fsPath], $zipPath, $fsPath]);
            } catch (Exception $ex) {
                app()->form("MainForm")->logger->console($ex->getMessage(), LoggerReporter::ERROR)->show();
                app()->form("MainForm")->logger->discord($ex->getMessage(), LoggerReporter::ERROR)->send();
            }
        }
        
        if (isset($fsPath)) {
            $filePath = $fsPath;
        }
        
        // чтобы не переотрисовывать по новой списко зависимостей
        if ($this->lastSelectedProject === $filePath) {
            return;
        }
        
        $this->lastSelectedProject = $filePath;
        
        if ($fs->isFile($filePath)) {
            $this->dependency->getDependencys($this->zipFiles[$filePath]->getZipInstance());
        }
    }
    
    
    public function onFileSystem (callable $callback) {
        if (!is_callable($callback)) return;
        $this->events["onFileSystem"] = $callback;
    }
    
    
    public function onZipFileSystem (callable $callback) {
        if (!is_callable($callback)) return;
        $this->events["onZipFileSystem"] = $callback;
    }
    
    
    public function getPaths ($filePath) {
        $fsPath = '';
        $zipPath = '';
        $found = false;
        
        foreach (explode(File::DIRECTORY_SEPARATOR, $filePath) as $chunk) {
            if (str::endsWith($chunk, '.zip')) {
                $fsPath .= $chunk;
                $found = true;
            } else {
                if (!$found) {
                    $fsPath .= $chunk . File::DIRECTORY_SEPARATOR;
                } else {
                    $zipPath .= $chunk . File::DIRECTORY_SEPARATOR;
                }
            }
        }
        
        $zipPath = substr($zipPath, 0, -1);
        
        $zipPath = str_replace(['\\', '/'], File::DIRECTORY_SEPARATOR, $zipPath);
        
        return [$fsPath, $zipPath];
    }
    
    protected function applyIcon ($item, $path) {

        if (!($item instanceof UXTreeItem || $item instanceof UXLabel)) {
            throw new IllegalArgumentException('$item must be instance UXTreeItem or UXLabel');
        }
        
        if ($path !== FSTreeProvider::EMPTY_PATH_ELEMENT) {
            if ($this->imageCache->exists(fs::ext($path))) {
                $item->graphic = new UXImageView($this->imageCache->get(fs::ext($path)));
                if (fs::ext($path) == 'php') {
                    $this->applyColor($item->graphic, [0.17, 0.57, 0.09, 0.9]);
                } else if (fs::ext($path) == 'fxml') {
                    $this->applyColor($item->graphic, [0.17, 0.57, -0.88, 0.9]);
                }
                return;
            }
            /* 
            $iconFileSelected = new IconFileSelected();
            $iconFileSelected->clear();
            
            switch (fs::ext($path)) {
                case 'png': 
                case 'gif': 
                case 'jpg': 
                case 'jpeg': 
                case 'ico': 
                    $iconFileSelected->setSize(14, 20, 5);
                    $iconFileSelected->updateText(fs::ext($path));
                    break;
                case 'zip': 
                    $iconFileSelected->updateClasses(["zip-icon"]);
                    $iconFileSelected->setSize(20, 17, 5);
                    $iconFileSelected->updateText("");
                    break;
                case 'php': 
                    var_dump($path);
                    $iconFileSelected->setSize(16, 20, 10);
                    $iconFileSelected->updateText(fs::ext($path));
                    $iconFileSelected->updateClasses(["file-icon", "red-color", "normal-size"]);
                    break;
                case 'fxml': 
                    $iconFileSelected->setSize(16, 20, 4);
                    $iconFileSelected->updateText(fs::ext($path));
                    $iconFileSelected->updateClasses(["file-icon", "blue-color", "small-size"]);
                    break;
                case 'css': 
                    $iconFileSelected->setSize(16, 20, 4);
                    $iconFileSelected->updateText(fs::ext($path));
                    $iconFileSelected->updateClasses(["file-icon", "green-color", "small-size"]);
                    break;
                default:
                    $iconFileSelected->setSize(16, 20, 1);
                    $iconFileSelected->updateText("");
            }
            
            $item->graphic = $iconFileSelected->getNode();
            */
            switch (fs::ext($path)) {
                case 'png': 
                case 'gif': 
                case 'jpg': 
                case 'jpeg': 
                case 'ico': 
                    $file = 'res://.data/img/ui/image-16.png'; break;
                case 'zip':
                    $item->graphic = new UXHBox();
                    $item->graphic->minWidth = 16;
                    $item->graphic->minHeight = 12;
                    $item->graphic->classes->add("zip-icon");

                    return;
                case 'php':
                    $file = 'res://.data/img/ui/php-file-60.png'; break;
                case 'fxml':
                    $file = 'res://.data/img/ui/fxml-file-24.png'; break;
                    
                default: $file = 'res://.data/img/ui/file-60.png';
            }
        } else {
            $item->graphic = new UXHBox();
            $item->graphic->minWidth = 16;
            $item->graphic->minHeight = 12;
            $item->graphic->classes->add("folder-icon");

            return;
        }
        
        $item->graphic = new UXImageView(new UXImage($file, 20, 20));
        
        // dublicate code
        if (fs::ext($path) == 'php') {
            $this->applyColor($item->graphic, [0.17, 0.57, 0.09, 0.9]);
        } else if (fs::ext($path) == 'fxml') {
            $this->applyColor($item->graphic, [0.17, 0.57, -0.88, 0.9]);
        }
        
        $this->imageCache->set(fs::ext($path), $item->graphic->image);
    }
    
    protected function applyColor ($node, $params) {
        $effect = new UXColorAdjustEffect();
        $effect->brightness = $params[0];
        $effect->contrast = $params[1];
        $effect->hue = $params[2];
        $effect->saturation = $params[3];
        
        $node->effects->add($effect);
    }
    
    
    protected function createTreeItemOfZip (UXTreeItem $root, $stat) {
        foreach (array_keys($stat) as $path) {
            if ($stat[$path]["crc"] == 0) continue; // fix bug with archive downloaded from githhub (draws wrong icons, all dirs was as file)
            
            $path = str_replace('\\', '/', $path);
            $_path = explode('/', $path);
            
            $this->treeHelper->makeTree($root, $_path, function ($node, bool $isDir) use ($path) {
                $this->applyIcon($node, ($isDir) ? FSTreeProvider::EMPTY_PATH_ELEMENT : $path);
            });
        }
                
    }
    
    

    

}