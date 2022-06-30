<?php
namespace app\helpers;

use app;
use gui;

trait Singletone 
{
    private static $instance;
    
    /**
     * @var MainForm
     */
    public $form;
    
    public static function getInstance ($form = null) {
        if (self::$instance === null) {
            self::$instance = new self($form);
        }
        
        return self::$instance;
    }
    
    private function __construct ($form) {
        $this->form = $form;
    }
}