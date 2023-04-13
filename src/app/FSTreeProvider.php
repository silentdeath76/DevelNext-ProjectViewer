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
            $items = explode('\\', $filePath);
            
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
        
        foreach (explode('\\', $filePath) as $chunk) {
            if (str::endsWith($chunk, '.zip')) {
                $fsPath .= $chunk;
                $found = true;
            } else {
                if (!$found) {
                    $fsPath .= $chunk . '\\';
                } else {
                    $zipPath .= $chunk . '\\';
                }
            }
        }
        
        $zipPath = substr($zipPath, 0, -1);
        
        $zipPath = str_replace('\\', '/', $zipPath);
        
        return [$fsPath, $zipPath];
    }
    
    protected function applyIcon ($item, $path) {

        if (!($item instanceof UXTreeItem || $item instanceof UXLabel)) {
            throw new IllegalArgumentException('$item must be instance UXTreeItem or UXLabel');
        }
        
        if ($path !== FSTreeProvider::EMPTY_PATH_ELEMENT) {
            if ($this->imageCache->exists(fs::ext($path))) {
                $item->graphic = new UXImageView($this->imageCache->get(fs::ext($path)));
                return;
            }
        
            switch (fs::ext($path)) {
                case 'png': 
                case 'gif': 
                case 'jpg': 
                case 'jpeg': 
                case 'ico': 
                    $file = 'res://.data/img/ui/image-16.png'; break;
                case 'zip':
                    // $file = 'res://.data/img/ui/archive-60.png'; break;
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
            $file = 'res://.data/img/ui/folder-60.png';
            
            $item->graphic = new UXHBox();
            $item->graphic->minWidth = 16;
            $item->graphic->minHeight = 12;
            $item->graphic->classes->add("folder-icon");
            
            
            
            
            return;
            
            if ($this->imageCache->exists(FSTreeProvider::EMPTY_PATH_ELEMENT)) {
                $item->graphic = new UXImageView($this->imageCache->get(FSTreeProvider::EMPTY_PATH_ELEMENT));
            } else {
                $item->graphic = new UXImageView(new UXImage($file, 20, 20));
                $this->imageCache->set(FSTreeProvider::EMPTY_PATH_ELEMENT, $item->graphic->image);
            }
            
            return;
        }
        
        $item->graphic = new UXImageView(new UXImage($file, 20, 20));
        $this->imageCache->set(fs::ext($path), $item->graphic->image);
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