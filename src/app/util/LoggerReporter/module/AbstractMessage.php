<?php


namespace app\util\LoggerReporter\module;


use app\util\LoggerReporter\LoggerReporter;
use php\time\Time;

abstract class AbstractMessage
{
    protected $message;
    protected $level;

    protected function getLevel(): string
    {
        $level = 'DEBUG';

        switch ($this->level) {
            case LoggerReporter::DEBUG:
                $level = 'DEBUG';
                break;
            case LoggerReporter::INFO:
                $level = 'INFO';
                break;
            case LoggerReporter::WARNING:
                $level = 'WARNING';
                break;
            case LoggerReporter::ERROR:
                $level = 'ERROR';
                break;
        }
        
        return $level;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getDate(): string
    {
        return Time::now()->toString('MM/dd/YYYY HH:mm:ss');
    }
}