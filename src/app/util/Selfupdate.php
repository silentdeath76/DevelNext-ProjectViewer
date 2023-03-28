<?php
namespace app\util;

use framework;
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
        $http = $this->http->get($url);
        
        if ($http->statusCode() !== 200) {
            return Releases::of(["error" => "cant connected to github.com"]);
        }
        
        $array = json_decode($http->body(), true);
        
        return Releases::of($array);
    }
    
    public function update () {
        
    }
}