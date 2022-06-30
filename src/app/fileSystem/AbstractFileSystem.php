<?php
namespace app\fileSystem;

use framework;
use std;
use gui;

abstract class AbstractFileSystem 
{
    /**
     * Дата создания файла
     * @param $path
     * @return string
     */
    public abstract function createdAt($path);

    /**
     * Дата модификации файла
     */
    public abstract function modifiedAt($path);

    /**
     * Размер файла
     */
    public abstract function size($path);

    /**
     * Проверка на существование файла
     */
    public abstract function exists($path);

    /**
     * Чтение файла
     */
    public abstract function read($path);

    /**
     * Создание файла
     */
    public abstract function make($path);

    /**
     * Удаление фала
     */
    public abstract function remove($path);

    /**
     * Переименование файла
     */
    public abstract function rename($path, $newName);

    /**
     * Является ли выбранный путь файлом
     */
    public abstract function isFile($path);

    /**
     * Является ли выбранный путь директорией
     */
    public abstract function isDirectory($path);

    /**
     * 
     */
    public abstract function getAbsolutePath(UXTreeItem $node, $path = null);
    
    
    protected function normalizePath ($path, $zip) {
        if (!$zip->has($path)) {
            $path = str_replace('/', '\\', $path);
                
            if (!$zip->has($path)) {
                $path = str_replace('\\', '/', $path);
                    
                if (!$zip->has($path)) {
                    // Logger::warn('Error wrong path');
                }
            }
        }
        
        return $path;
    }
    
    protected function backTrace(UXTreeItem $item, $path = null) {
        
        if ($item->parent instanceof UXTreeItem) {
            if (fs::name($this->path) === $item->parent->value) {
                return $path . $item->parent->value;
            }
            
            return $this->backTrace($item->parent, $path) . '\\' . $item->value;
        }
        
        return $path;
    }
}