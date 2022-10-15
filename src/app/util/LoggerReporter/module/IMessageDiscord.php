<?php


namespace app\util\LoggerReporter\module;


use app\util\LoggerReporter\interfaces\IMessageObject;

interface IMessageDiscord extends IMessageObject
{
    public function send();
}