<?php

namespace AutoUFSM\Console;

require __DIR__ . '/../vendor/autoload.php';

use AutoUFSM\Command\Agendar;
use Symfony\Component\Console\Application;

setlocale(LC_ALL,'pt_BR.UTF-8');

$application = new Application;

$application->add(new Agendar);

try {
    $application->run();
} catch (\Exception $e) {
    var_dump($e);
}

