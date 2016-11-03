<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Predis\Client;

class TestEmailCommand extends ConsoleKit\Command
{

    public function execute(array $args, array $options = array())
    {
        $logger = new Logger('test-command');
        $logger->pushHandler(new StreamHandler('test-command.log', Logger::DEBUG));

        $queue = new Horn\Queue($logger, "http://127.0.0.1:4151");

        $mail = new Horn\Mail($logger, $queue);

        $mail->push("514412334@qq.com", "signup", "");
    }

}