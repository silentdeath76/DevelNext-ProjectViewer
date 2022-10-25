<?php


namespace app\util\LoggerReporter\interfaces;


interface IMessageObject
{
    public function setMessage($message);
    public function setLevel($level);

    public function getDate(): string;
}