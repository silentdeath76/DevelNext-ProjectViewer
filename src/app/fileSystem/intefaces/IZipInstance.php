<?php
namespace app\fileSystem\intefaces;

use php\compress\ZipFile;

interface IZipInstance 
{
    /**
     * @return ZipFile
     */
    public function getZipInstance ();
    
    /**
     * @param ZipFile $zip
     */
    public function setZipInstance (ZipFile $zip);
}