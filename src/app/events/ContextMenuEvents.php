<?php
namespace app\events;

use framework;
use gui;
use php\compress\ZipFile;
use std;
use app;

class ContextMenuEvents 
{
    const CONTEXT_MENU_X = 'ui.contextMenu.x';
    const CONTEXT_MENU_Y = 'ui.contextMenu.y';
    
    use Singletone;
    
    public function saveAs () {
        list($zipFile, $innerPath) = $this->getPath();
        
        $zip = $this->form->fsTree->getZipByNode($this->form->tree->focusedItem)->getZipInstance();
        
        if (!$zip->has($innerPath)) {
            $innerPath = str_replace('/', '\\', $innerPath);
                
            if (!$zip->has($innerPath)) {
                $innerPath = str_replace('\\', '//', $innerPath);  
                
                if (!$zip->has($innerPath)) {
                   
                }
            }
        }

        $zip->read($innerPath, function ($stat, Stream $stream) use ($innerPath) {
            $saveDialog = new UXFileChooser();
            $saveDialog->initialFileName = fs::name($innerPath);
            if (($file = $saveDialog->showSaveDialog($this->form)) instanceof File) {
                $fstream = new FileStream($file, 'w+');
                $fstream->write($stream);
                $fstream->close();
            }
        });
    }
    
    
    public function rename () {
        list($zipFile, $innerPath) = $this->getPath();
        
        // $zip = new ZipFile();
        $zip = $this->form->fsTree->getZipByNode($this->form->tree->focusedItem)->getZipInstance();
        if (($newName = UXDialog::input(fs::name($innerPath), fs::name($innerPath), $this->form)) != null) {

            if ($newName == fs::name($innerPath)) {
                return;
            }
            
            
            if (!$zip->has($innerPath)) {
                $innerPath = str_replace('/', '\\', $innerPath);
                
                if (!$zip->has($innerPath)) {
                    $innerPath = str_replace('\\', '//', $innerPath);
                    
                    if (!$zip->has($innerPath)) {
                        Logger::warn('Error wrong path');
                    }
                }
            }
            
            $zip->read($innerPath, function ($stat, Stream $stream) use ($zip, $innerPath, $newName) {
                $memory = new MemoryStream();
                $memory->write($stream->readFully());
                
                $th = new Thread(function () use ($zip, $innerPath, $newName, $memory) {
                    $memory->seek(0);
                    
                    if (fs::parent($innerPath) !== null) {
                        $path .= fs::parent($innerPath) . '\\';
                    } else {
                        $path = fs::parent($innerPath);
                    }
                    
                    $path = str_replace('/', '\\', $path);
                    
                    $zip->add($path . $newName, $memory, 8);
                    $zip->remove($innerPath);
                    
                    $this->form->tree->focusedItem->value = $newName;
                });
                $th->setDaemon(true);
                $th->start();
            });
        }
    }
    
    
    public function delete () {
        list($zipFile, $innerPath) = $this->getPath();
        
        // $zip = new ZipFile();
        $zip = $this->form->fsTree->getZipByNode($this->form->tree->focusedItem)->getZipInstance();
        
        if (UXDialog::confirm('Вы уверены что хотит удалить - ' . $innerPath . '?', $this->form)) {
            if (!$zip->has($innerPath)) {
                $innerPath = str_replace('/', '\\', $innerPath);
                
                if (!$zip->has($innerPath)) {
                    $innerPath = str_replace('\\', '//', $innerPath);
                    
                    if (!$zip->has($innerPath)) {
                        Logger::warn('Error wrong path');
                    }
                }
            }
            
            // хз почему, но он удаляет и корневую директорию если там не осталось файлов
            $zip->remove($innerPath);
            $this->form->tree->focusedItem->parent->children->remove($this->form->tree->focusedItem);
        }
    }
    
    
    public function fullInfo (UXEvent $event = null) {
        list($zipFile, $innerPath) = $this->getPath();
        
        $zip = $this->form->fsTree->getZipByNode($this->form->tree->focusedItem)->getZipInstance();
        
        if (!$zip->has($innerPath)) {
            $innerPath = str_replace('/', '\\', $innerPath);
                
            if (!$zip->has($innerPath)) {
                $innerPath = str_replace('\\', '//', $innerPath); 
                
                if (!$zip->has($innerPath)) {
                    Logger::warn('Error wrong path');
                }
            }
        }
        
        if ($zip->has($innerPath)) {
            $this->form->informPanel = new UXScrollPane(new UXVBox);
            $this->form->informPanel->content->padding = 10;
            $this->form->informPanel->style = '-fx-effect: dropshadow(gaussian, rgba(0, 0, 0, 0.3), 10, 0.5, 0.0, 0.0);';
            $this->form->informPanel->classes->add("file-info");
            $this->form->informPanel->on("mouseExit", function ($e) {
                $e->sender->free();
            });
            
            foreach ($zip->stat($innerPath) as $key => $value) {
                if ($value == null) $value = 'null';
                
                $this->form->informPanel->content->add($box = new UXHBox([$key = new UXLabel($key), $value = new UXLabel($value)], 2));
                
                $box->paddingTop = 2;
                $box->paddingBottom = 2;
                $box->paddingLeft = 5;
                $box->paddingRight = 5;
                
                $key->style = '-fx-font-weight: bold;';
                $key->minWidth = 100;
            }
            
            $this->form->informPanel->x = $this->form->getConfig()->get(ContextMenuEvents::CONTEXT_MENU_X);
            $this->form->informPanel->y = $this->form->getConfig()->get(ContextMenuEvents::CONTEXT_MENU_Y);
            
            $this->form->add($this->form->informPanel);
        }
    }
    
    public function showInExplorer (UXEvent $event = null) {
        $path = $this->form->fsTree->getFileByNode($this->form->tree->focusedItem)->getAbsolutePath($this->form->tree->focusedItem);
        execute('explorer.exe /select,' . fs::normalize($this->form->projectDir . '/' . $path));
    }
    
    
    
    private function getPath () {
        $path = $this->form->fsTree->backTrace($this->form->tree->focusedItem);
        return $this->form->fsTree->getPaths($path);
        
        // return [str::sub($path, 0, str::pos($path, '/')), str::sub($path, str::pos($path, '/') + 1)];
    }
}