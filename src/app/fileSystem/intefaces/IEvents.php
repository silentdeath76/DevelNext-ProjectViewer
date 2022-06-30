<?php
namespace app\fileSystem\intefaces;

interface IEvents 
{
    /**
     * 
     */
    public function onFileSystem(callable $callback);

    /**
     * 
     */
    public function onZipFileSystem(callable $callback);
}