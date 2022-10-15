<?php


namespace app\util\LoggerReporter\module;


use app\util\LoggerReporter\interfaces\IMessageObject;

interface IMessageConsole extends IMessageObject
{
    public function show();
}