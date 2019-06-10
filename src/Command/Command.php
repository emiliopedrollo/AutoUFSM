<?php

namespace AutoUFSM\Command;

use Carbon\Carbon;
use ErrorException;
use Phar;
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
//            'Content-Type' => 'application/x-www-form-urlencoded',
            'Content-Type' => 'application/json',
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

    /**
     * @param OutputInterface $output
     * @throws ErrorException|ParseException
     */
    protected function loadYaml(OutputInterface $output)
    {
        try {
            $load_path = $this->getRealPath($this->load_filename);
            if (is_dir($load_path)) {
                $load_path = rtrim($load_path,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                if ($handle = opendir($load_path)) {
                    $this->load = [];
                    while (false !== ($file = readdir($handle))) {

                        if ((filetype($load_path . $file) != 'file') OR
                            (pathinfo($load_path . $file)['extension'] != 'yml'))
                        {
                            continue;
                        }

                        $this->load[] = Yaml::parseFile($load_path . $file);

                    }
                    closedir($handle);
                } else {
                    $output->writeln("Could not pen load folder ($this->load_filename), aborting.");
                    throw new ErrorException;
                }
            } else {
                $this->load = Yaml::parseFile($load_path);
            }
        } catch (ParseException $e) {
            $output->writeln("Could not parse load file ($this->load_filename), aborting: " .
                $e->getMessage());
            throw $e;
        }

    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        $file = $this->getRealPath($input->getOption('load')) ?: ((Phar::running()) ?
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

    protected function log(OutputInterface $output, string $text) {
        $output->writeln(sprintf("[%s] %s",
            Carbon::now()->toDateTimeString(),
            $text
        ));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws ErrorException|ParseException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadYaml($output);
    }
}
