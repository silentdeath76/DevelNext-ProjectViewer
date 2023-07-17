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
     * @var Response
     */
    private $response;
    
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
            return Releases::of([
                "error" => "Error connected to github.com",
                "status" => $http->statusCode(),
                "headers" => $http->headers(),
                "response" => json_decode($http->body(), true)
            ]);
        }
        
        $array = json_decode($http->body(), true);
        
        $this->response = Releases::of($array);

        return $this->response;
    }
    
    public function download ($tempFile) {
        $http = new HttpClient();
        $stream = $http->get($this->response->getLink())->body();
        $outputStream = new FileStream($tempFile, "w+");
        $outputStream->write($stream);
        $outputStream->close();
    }
}