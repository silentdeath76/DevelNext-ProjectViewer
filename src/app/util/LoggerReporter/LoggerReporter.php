<?php


namespace app\util\LoggerReporter;


use app\util\LoggerReporter\module\{AbstractMessage, MessageConsole, MessageDiscord};

class LoggerReporter
{
    const DEBUG = 1;
    const INFO = 2;
    const WARNING = 3;
    const ERROR = 4;

    /**
     * @var AbstractMessage[]
     */
    private $instances;

    /**
     * LoggerReporter constructor.
     */
    public function __construct()
    {
        $this->instances = [
            "console" => new MessageConsole(),
            "discord" => new MessageDiscord()
        ];
        
        $this->instances["discord"]->updateApiKey(LoggerReporter::INFO, "817425028555210762/2wmTRtl_DAxp7Vn5DV4elnkUtVEBXvSLPEe_vfqid5HivgreHQIJnljUHJ6mfjC3eQnB");
        $this->instances["discord"]->updateApiKey(LoggerReporter::ERROR, "792539671787864064/dSSQtqIT92pibneJwHW6sbca95ZSk3MU8xasR3hALzpnzW_Oo4WxldakZuljwM9EGJyE");
        $this->instances["discord"]->updateApiKey(LoggerReporter::WARNING, "792539671787864064/dSSQtqIT92pibneJwHW6sbca95ZSk3MU8xasR3hALzpnzW_Oo4WxldakZuljwM9EGJyE");
    }


    /**
     * @param $message
     * @param int $level
     * @return MessageConsole
     */
    final public function console($message, $level = self::DEBUG): MessageConsole
    {
        $this->instances["console"]->setMessage($message);
        $this->instances["console"]->setLevel($level);

        return $this->instances["console"];
    }

    /**
     * @param $message
     * @param int $level
     * @return MessageDiscord
     */
    final public function discord($message, $level = self::INFO): MessageDiscord
    {
        $this->instances["discord"]->setMessage($message);
        $this->instances["discord"]->setLevel($level);

        return $this->instances["discord"];
    }
}