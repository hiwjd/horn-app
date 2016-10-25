<?php
require __DIR__ . '/../vendor/autoload.php';

include 'TestMessageCommand.php';
include 'TestCommand.php';

$console = new ConsoleKit\Console();
$console->addCommand('TestMessageCommand');
$console->addCommand('TestCommand');
$console->run();