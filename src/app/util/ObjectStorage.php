<?php


namespace app\util;

use app;


class ObjectStorage
{
    private $storage = [];

    public function set($key, $object): void
    {
        $this->storage[$key] = $object;
    }

    public function get($key)
    {
        return $this->storage[$key];
    }

    public function remove($key): void
    {
        unset($this->storage[$key]);
    }

    public function exists($key): bool
    {
        return array_key_exists($key, $this->storage);
    }
    
    public function getAllInstanceOf ($string): array 
    {
        $temp = [];
        
        foreach ($this->storage as $key => $value) {
            if ($value instanceof $string) $temp[$key] = $value;
        }
        
        return $temp;
    }
}