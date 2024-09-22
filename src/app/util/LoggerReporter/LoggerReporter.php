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
        
        $this->instances["discord"]->updateApiKey(LoggerReporter::INFO, "1244606300327772240/Y2j908292-QtxKJZRfOghKLz17TTmzfBkIU7ixTGdwRgrtuOLIubdBF3cWTfOAvZGcJt");
        $this->instances["discord"]->updateApiKey(LoggerReporter::ERROR, "1242749474892419092/KZA7cCGu8SNN7BE-vf0XCX_zbT-9kD_fJWVdiKmUXOisDpAvNEtv3OFla0ea-oIvRFAC");
        $this->instances["discord"]->updateApiKey(LoggerReporter::WARNING, "1242749474892419092/KZA7cCGu8SNN7BE-vf0XCX_zbT-9kD_fJWVdiKmUXOisDpAvNEtv3OFla0ea-oIvRFAC");
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