<?php
namespace app;

use framework;
use php\compress\ZipFile;
use std;
use gui;
use app;

class FSTreeProvider implements IEvents
{
    /**
     * @var Dependency
     */
    private $dependency;
    
    /**
     * @var UXTreeItem
     */
    private $rootItem;
    
    private $lastSelectedProject;
    
    private $selectedDirectory;

    public function __construct (UXTreeItem $rootTreeItem) {
        $this->rootItem = $rootTreeItem;
        $this->dependency = new Dependency();
    }
    
    public function setDirectory ($path) {
        $this->selectedDirectory = $path;
        
        fs::scan($path, ['extensions' => ['zip'], 'callback' => function (File $file) {
            $filePath = str::sub($file->getAbsoluteFile(), strlen($this->selectedDirectory) + 1);
            $this->zipFiles[$file->getAbsoluteFile()] = new ZipFile($file);
            $items = explode('\\', $filePath);
            
            if ($file->isFile()) {
                $this->createTreeItem($this->rootItem, $items);
            }
        }]);
        
        $this->sort($this->rootItem);
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
        
        if ($fs->isFile($filePath)) {
            
            // show by explorer
        } else {
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
                $this->sort($item);
            }
            
            call_user_func_array($this->events["onFileSystem"], [$fs, $filePath]);
        } else if ($fs->isDirectory($filePath)) {
            call_user_func_array($this->events["onFileSystem"], [$fs, $filePath]);
        } else { // если выбранный елемент является файлом в zip архиве
            list($fsPath, $zipPath) = $this->getPaths($filePath);
            call_user_func_array($this->events["onZipFileSystem"], [$this->zipFiles[$fsPath], $zipPath, $fsPath]);
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
        // var_dump($fsPath);
        
        $zipPath = str_replace('\\', '/', $zipPath);
        
        return [$fsPath, $zipPath];
    }
    
    protected function sort ($node) {
        if ($node->children->isNotEmpty()) {
            $notempty = [];
            $empty = [];
            
            // грубая сортировка с разделением на пустые и с под элементами ноды
            foreach ($node->children as $key => $children) {
                if ($children->children->isNotEmpty()) {
                    $notempty[] = $children;
                } else {
                    $empty[] = $children;
                }
            }
            
            uasort($notempty, function ($a, $b) {
                return str::compare($a->value, $b->value);
            });
            
            uasort($empty, function ($a, $b) {
                return str::compare($a->value, $b->value);
            });
            
            $node->children->clear();
            $node->children->addAll($notempty);
            $node->children->addAll($empty);
            
            foreach ($node->children as $children) {
                $this->sort($children);
            }
        }
    }
    
    protected function applyIcon ($item, $path) {
        if ($path !== 'path') {
            switch (fs::ext($path)) {
                case 'png': 
                case 'gif': 
                case 'jpg': 
                case 'jpeg': 
                case 'ico': 
                    $file = 'res://.data/img/ui/image-16.png'; break;
                case 'zip':
                    $file = 'res://.data/img/ui/archive-16.png'; break;
                case 'php':
                    $file = 'res://.data/img/ui/php-file-16.png'; break;
                case 'fxml':
                    $file = 'res://.data/img/ui/fxml-file-16.png'; break;
                            
                default: $file = 'res://.data/img/ui/file-16.png';
            }
        } else {
            $file = 'res://.data/img/ui/folder-16.png';
        }
        
        if (!($item instanceof UXTreeItem || $item instanceof UXLabel)) {
            throw new IllegalArgumentException('wtf?');
        }
        
        if (!($image instanceof UXImage)) {
            $image = new UXImage($file);
        }
        
        $item->graphic = new UXImageView($image);
        $item->graphic->width = 16;
        $item->graphic->height = 16;
    }
    
    protected function createTreeItemOfZip (UXTreeItem $root, $stat) {
        foreach (array_keys($stat) as $path) {
            $path = str_replace('\\', '/', $path);
            $path = explode('/', $path);
            $this->createTreeItem($root, $path);
        }
    } 
    
    protected function createTreeItem (UXTreeItem $root, $items) {
        foreach ($items as $key => $value) {
            $value = trim($value);
            
            if (empty($value)) continue;
            
            if ($root->children->count() > 0) {
                foreach ($root->children->toArray() as $child) {
                    if ($child->value === $value) {
                        $this->createTreeItem($child, array_slice($items, 1));
                        return;
                    }
                }
            }
            
            $root->children->add($root = new UXTreeItem($value));
            $this->applyIcon($root, "path");
        }
        
        
       $this->applyIcon($root, $value);
    }
    
    public function backTrace(UXTreeItem $item, $path = null) {
        
        if ($item->parent instanceof UXTreeItem) {
            if (fs::name($this->path) === $item->parent->value) {
                return $path . $item->parent->value;
            }
            
            return $this->backTrace($item->parent, $path) . '\\' . $item->value;
        }
        
        return $path;
    }
}