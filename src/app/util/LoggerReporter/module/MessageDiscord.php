<?php


namespace app\util\LoggerReporter\module;


use app;
use httpclient;
use app\util\exception\RuntimeException;
use php\io\File;

class MessageDiscord extends AbstractMessage implements IMessageDiscord
{
    /**
     * @var array
     */
    private $apiKeys = [];
    private $baseUrl = 'https://discord.com/api/webhooks/';

    /**
     * @var HttpClient
     */
    private $http;

    public function __construct()
    {
        $this->http = new HttpClient();
    }

    public function updateApiKey($event, $key)
    {
        $this->apiKeys[$event] = $key;
    }

    /**
     * @throws RuntimeException
     */
    public function send()
    {
        if (!isset($this->apiKeys[$this->level])) {
            throw new RuntimeException("Please set discord api key for event: " . $this->getLevel());
        }

        if (empty($this->message)) return;

        $messageBody = sprintf("[%s] [%s] %s", $this->getLevel(), $this->getDate(), $this->message);

        $this->http->requestType = 'URLENCODE';
        $this->http->postAsync($this->baseUrl . $this->apiKeys[$this->level], [
            "content" => $messageBody,
            "username" => "App reporter v" . AppModule::APP_VERSION
        ]);

    }

    /**
     * @param File $logFile
     */
    public function attachFile(File $logFile)
    {
        $messageBody = sprintf("[%s] [%s] %s", $this->getLevel(), $this->getDate(), $this->message);

        $this->http->requestType = 'MULTIPART';
        $this->http->postAsync($this->baseUrl . $this->apiKeys[$this->level], [
            "content" => $messageBody,
            "username" => "App reporter v" . AppModule::APP_VERSION,
            "files" => $logFile
        ]);
    }
}