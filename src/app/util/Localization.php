<?php
namespace app\util;

use app;
use Exception;
use std;

class Localization 
{
    private static $list = [];
    
    public static function load ($path) {
        self::$list = self::parse(FileStream::of($path)->readFully());
    }
    
    public static function get ($key) {
        return self::$list[$key];
    }
    
    private static function parse ($data) {
        $result = [];
        foreach (explode("\n", $data) as $line) {
            $pair = explode('=', $line);
            $result[trim($pair[0])] = trim($pair[1]);
        }
        return $result;
    }
}