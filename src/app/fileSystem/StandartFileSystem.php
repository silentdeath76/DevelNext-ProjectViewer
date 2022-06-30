<?php
namespace app\fileSystem;

use framework;
use std;
use gui;
use app;

class StandartFileSystem extends AbstractFileSystem
{
    /**
     * Дата создания файла
     */
    public function createdAt($path) {
        return new Time(filectime($path) * 1000)->toString('dd/MM/YYYY HH:mm:ss');
    }

    /**
     * Дата модификации файла
     */
    public function modifiedAt($path) {
        return new Time(filemtime($path) * 1000)->toString('dd/MM/YYYY HH:mm:ss');
    }

    /**
     * Размер файла
     */
    public function size($path) {
        return filesize($path);
    }

    /**
     * Проверка на существование файла
     */
    public function exists($path) {
        return fs::exists($path);
    }

    /**
     * Чтение файла
     */
    public function read($path) {
        return Stream::getContents($path);
    }

    /**
     * Создание файла
     */
    public function make($path) {
        Logger::warn('Пока что не реализованно, и возможно не будет');
    }

    /**
     * Удаление фала
     */
    public function remove($path) {
        fs::delete($path);
    }

    /**
     * Переименование файла
     */
    public function rename($path, $newName) {
        fs::rename($path, $newName);
    }

    /**
     * Является ли выбранный путь файлом
     */
    public function isFile($path) {
        if (file_exists($path))
            return is_file($path);
            
        return false;
    }

    /**
     * Является ли выбранный путь директорией
     */
    public function isDirectory($path) {
        if (file_exists($path))
            return is_dir($path);
            
        return false;
    }

    public function getAbsolutePath (UXTreeItem $item, $path = null) {
        return $this->backTrace($item);
    }
}