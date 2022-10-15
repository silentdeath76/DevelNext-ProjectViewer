<?php

namespace app\util\LoggerReporter\module;


class MessageConsole extends AbstractMessage implements IMessageConsole
{
    public function show()
    {
        print_r(sprintf("[%s] [%s] %s\n", $this->getLevel(), $this->getDate(), $this->message));
    }

}