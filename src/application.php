<?php

namespace AutoUFSM\Console;

require __DIR__ . '/../vendor/autoload.php';

use AutoUFSM\Command\Agendar;
use AutoUFSM\Command\GetToken;
use AutoUFSM\Command\VerificaTokens;
use Symfony\Component\Console\Application;

setlocale(LC_ALL,'pt_BR.UTF-8');

$application = new Application;

$application->add(new Agendar);
$application->add(new GetToken);
$application->add(new VerificaTokens);

try {
    $application->run();
} catch (\Exception $e) {
    var_dump($e);
}

