<?php
namespace app\util;

use app;
use std, httpclient;

class Selfupdate 
{
    private $url = 'https://api.github.com/repos/{OWNER}/{REPO}/releases/latest';
    private $owner;
    private $repos;
    
    /**
     * @var HttpClient
     */
    private $http;
    
    public function __construct ($owner, $repos) {
        $this->owner = $owner;
        $this->repos = $repos;
        $this->http  = new HttpClient();
    }
    
    /**
     * @return Releases
     */
    public function getLatest () {
        $url = str_replace(["{OWNER}", "{REPO}"], [$this->owner, $this->repos], $this->url);
        $array = json_decode($this->http->get($url)->body(), true);
        
        return Releases::of($array);
    }
    
    public function update () {
        
    }
}