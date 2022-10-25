<?php
namespace app\fileSystem;

use framework;
use std;
use gui;
use php\compress\ZipFile;
use app;

class ZipFileSystem extends AbstractFileSystem implements IZipInstance
{
    /**
     * @var ZipFile
     */
    private $zip;
    
    public function __construct () {
        // Logger::debug("Create new instance " . __CLASS__);
    }
    
    /**
     * Дата создания файла
     */
    public function createdAt($path) {
        $path = $this->normalizePath($path, $this->zip);
            
        if ($this->zip->has($path)) {
            return new Time($this->zip->stat($path)["time"])->toString(AbstractFileSystem::DATE_FORMAT);
        }
    }

    /**
     * Дата модификации файла
     */
    public function modifiedAt($path) {
        return $this->createdAt($path);
    }

    /**
     * Размер файла
     */
    public function size($path) {
        $path = $this->normalizePath($path, $this->zip);
        return $this->zip->stat($path)["size"];
    }

    /**
     * Проверка на существование файла, директории не может проверить на существование
     */
    public function exists($path) {
        $path = $this->normalizePath($path, $this->zip);
        return $this->zip->has($path);
    }

    /**
     * Чтение файла
     */
    public function read($path) {
        $temp = '';
        $path = $this->normalizePath($path, $this->zip);
        $this->zip->read($path, function (array $stat, Stream $stream) use (&$temp) {
            $temp = (string)$stream;
        });
        
        return $temp;
    }

    /**
     * Создание файла
     */
    public function make($path) {
        $path = $this->normalizePath($path, $this->zip);
        Logger::warn('Пока что не реализованно, и возможно не будет');
    }

    /**
     * Удаление фала
     */
    public function remove($path) {
        $path = $this->normalizePath($path, $this->zip);
        $this->zip->remove($path);
    }

    /**
     * Переименование файла
     */
    public function rename($path, $newName) {
        if ($path === $newName) return true;
        $path = $this->normalizePath($path, $this->zip);
        
        $oldData = $this->read($path);
        $this->zip->addFromString($newName, $oldData);
        
        if ($this->read($newName) == $oldData) {
            $this->remove($path);
            return true;
        }
        
        return false;
    }

    /**
     * Является ли выбранный путь файлом
     */
    public function isFile($path) {
        return $this->zip->has($this->normalizePath($path, $this->zip));
    }

    /**
     * Является ли выбранный путь директорией
     */
    public function isDirectory($path) {
        return !$this->zip->has($this->normalizePath($path, $this->zip));
    }

    /**
     * @return ZipFile
     */
    public function getZipInstance () {
        return $this->zip;
    }
    
    /**
     * @param ZipFile $zip
     */
    public function setZipInstance (ZipFile $zip) {
        $this->zip = $zip;
    }
    
    public function getAbsolutePath (UXTreeItem $item, $path = null) {
        return $this->backTrace($item);
    }
}