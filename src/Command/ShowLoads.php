<?php

namespace AutoUFSM\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ShowLoads extends Command
{

    protected function configure() {
        parent::configure();
        $this
            ->setName("show-loads")
            ->setDescription("Exibe informações sobre as cargas (loads) configuradas");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $output->write(Yaml::dump($this->load,4));
    }
}