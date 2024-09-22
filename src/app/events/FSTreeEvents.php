<?php
namespace app\events;

use std;
use std;
use app;

class FSTreeEvents 
{
    public function onFileSystem (StandartFileSystem $provider, $path = null)
    {
        $this->getForm()->fileInfoPanel->updateFilePath($path);
                
        $this->getForm()->fileInfoPanel->updateFileIcon($provider, $path);
        $this->getForm()->updateFileinfo($provider, $path);
    }
    
    public function onZipFileSystem (ZipFileSystem $provider, $zipPath, $path)
    {
        $this->getForm()->fileInfoPanel->updateFilePath($zipPath);
                
        $zipPath = $this->normalizeZipPath($provider, $zipPath);
         
        $this->getForm()->fileInfoPanel->updateFileIcon($provider, $zipPath);
        
        if ($provider->isFile($zipPath)) {
            $provider->getZipInstance()->read($zipPath, function (array $stat, Stream $output) use ($zipPath) {
                $this->getForm()->showMeta($stat);
                
                $ext = $this->getForm()->getHighlightType(fs::ext($zipPath));
                        
                if ($this->getForm()->findOperation($zipPath, $output, $ext) === false) {
                    $this->getForm()->showCodeInBrowser($output->readFully(), $ext);
                }
            });
            
            $this->getForm()->updateFileinfo($provider, $zipPath);
        } else if ($provider->isDirectory($zipPath)) {
            $this->getForm()->fileInfoPanel->updateFileSize("unknown");
        }
    }
    
    private function normalizeZipPath ($provider, $zipPath)
    {
        if (!$provider->getZipInstance()->has($zipPath)) {
            $zipPath = str_replace('\\', '/', $zipPath);
                    
            if (!$provider->getZipInstance()->has($zipPath)) {
                $zipPath = str_replace('/', '\\', $zipPath);
            }
        }
        
        return $zipPath;
    }
    
    /**
     * @return MainForm
     */
    private function getForm ()
    {
        return app()->form("MainForm");
    }
}