<?php
namespace app\util\dto;

use Exception;
use app, std;

class Releases {
    private $version;
    private $link;
    private $name;

    /**
     * @return Releases
     */
    public static function of ($obj) {
        if (!is_array($obj)) {
            throw new IllegalArgumentException("Argument must be array");
        }
        
        if (isset($obj["error"])) {
            throw new RuntimeException($obj["error"]);
        }
        
        if (!isset($obj["tag_name"]) || !isset($obj["assets"])) {
            throw new RuntimeException("Broken array object");
        }
    
        return new self($obj);
    }
    
    private function __construct ($obj) {
        $tag_name = $obj["tag_name"];
        
        $this->version = Regex::of('_v(.*?)$')->with($tag_name)->all()[0][1];
        $this->link = $obj["assets"][0]["browser_download_url"];
        $this->name = $obj["assets"][0]["name"];
    }
    
    public function getVersion () {
        return $this->version;
    }
    
    public function getLink () {
        return $this->link;
    }
    
    public function getName () {
        return $this->name;
    }
}