<?php

namespace AutoUFSM\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;


class Command extends BaseCommand
{
    protected $load;
    protected $load_filename;

    protected function configure(){
        $this->addOption('load','l',InputOption::VALUE_OPTIONAL,"The load file",null);
    }

    protected function getAPIHeaders($user)
    {
        return [
            'X-UFSM-Access-Token' => $user['Access Token'],
            'X-UFSM-Device-ID' => $user['Device Id'],
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'okhttp/3.9.0'
        ];
    }

    protected function getRealPath($path): ?string
    {
        if ($path === null) return null;

        if (function_exists('posix_getuid') && strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }

        return realpath($path);

    }

    protected function loadYaml(OutputInterface $output)
    {
        try {
            $this->load = Yaml::parseFile($this->getRealPath($this->load_filename));
        } catch (ParseException $e) {
            $output->writeln("Could not parse load file ($this->load_filename), aborting: " .
                $e->getMessage());
            throw $e;
        }

    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        $file = $this->getRealPath($input->getOption('load')) ?: ((\Phar::running()) ?
            getcwd() . "/load.yml" : __DIR__ . "/../../load.yml");

        if (file_exists($file)) {
            $this->load_filename = $file;
        } else {
            $output->writeln("Could not find a load.yml file");
        }

    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->load_filename) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question("Please provide the location of a valid load.yml file: ");

            $this->load_filename = $helper->ask($input, $output, $question);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadYaml($output);
    }
}