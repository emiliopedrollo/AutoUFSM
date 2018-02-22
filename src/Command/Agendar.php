<?php

namespace AutoUFSM\Command;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Collection;

class Agendar extends Command {


    protected $load;
    protected $load_filename;

    protected function configure() {
        $this
            ->setName("agendar")
            ->setDescription("Realiza agendamentos")
        ->addOption('load','l',InputOption::VALUE_OPTIONAL,"The load file",null);
    }

    protected function getAPIHeaders($user) {
        return [
            'X-UFSM-Access-Token' => $user['Access Token'],
            'X-UFSM-Device-ID' => $user['Device Id'],
            'Content-Type'=> 'application/x-www-form-urlencoded',
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'okhttp/3.9.0'
        ];
    }

    protected function getRealPath($path): ?string {
        if ($path === null) return null;

        if (function_exists('posix_getuid') && strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }

        return realpath($path);

    }

    protected function loadYaml(OutputInterface $output){
        try {
            $this->load = Yaml::parseFile($this->getRealPath($this->load_filename));
        } catch (ParseException $e) {
            $output->writeln("Could not parse load file ($this->load_filename), aborting: ".
                $e->getMessage());
            throw $e;
        }

    }

    protected function initialize(InputInterface $input, OutputInterface $output) {

        $file = $this->getRealPath($input->getOption('load')) ?: ((\Phar::running())?
            getcwd() . "/load.yml" : __DIR__ . "/../../load.yml");

        if (file_exists($file)) {
            $this->load_filename = $file;
        } else {
            $output->writeln("Could not find a load.yml file");
        }

    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->load_filename){
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question("Please provide the location of a valid load.yml file: ");

            $this->load_filename = $helper->ask($input,$output,$question);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->loadYaml($output);

        $agora = Carbon::now();

        $client = new Client();

        foreach ($this->load as $user) {
            if (isset($user['Agendamentos'])) {

                $request = $client->request('post', 'https://portal.ufsm.br/mobile/webservice/ru/agendamentoForm',
                    [
                        'headers' => $this->getAPIHeaders($user)
                    ]
                );

                $form = json_decode((string)$request->getBody());
                $restaurantes = new Collection($form->restaurantes);
                $tipos_refeicao = new Collection($form->tiposRefeicao);

                $data_maxima = (clone $agora)->addDays($form->quantidadeMaximaDias)->endOfDay();
                $data_processavel = (clone $agora)->startOfDay();

                while($data_processavel->lessThanOrEqualTo($data_maxima)) {

                    switch ($data_processavel->dayOfWeek){
                        case Carbon::SUNDAY: $dia = 'Dom'; break;
                        case Carbon::MONDAY: $dia = 'Seg'; break;
                        case Carbon::TUESDAY: $dia = 'Ter'; break;
                        case Carbon::WEDNESDAY: $dia = 'Qua'; break;
                        case Carbon::THURSDAY: $dia = 'Qui'; break;
                        case Carbon::FRIDAY: $dia = 'Sex'; break;
                        case Carbon::SATURDAY: $dia = 'Sab'; break;
                    }

                    if (isset($user['Agendamentos'][$dia])){
                        $refeicoes = new Collection($user['Agendamentos'][$dia]);
                        $refeicoes->each(function($refeicao_dados,$refeicao_text) use ($data_processavel, $user, $client, $restaurantes, $tipos_refeicao) {

                            $refeicao = $tipos_refeicao->where('descricao','=',$refeicao_text)->first();

                            $restaurante_text = $refeicao_dados['Restaurante'];
                            $restaurante = $restaurantes->where('nome','=',$restaurante_text)->first();

                            $client->request('post','https://portal.ufsm.br/mobile/webservice/ru/agendaRefeicoes',
                                [
                                    'headers' => $this->getAPIHeaders($user),
                                    'body' => json_encode([
                                        'dataFim' => $data_processavel->format('Y-m-d H:i:s'),
                                        'dataInicio' => $data_processavel->format('Y-m-d H:i:s'),
                                        'idRestaurante' => $restaurante->id,
                                        'tiposRefeicoes' => [
                                            [
                                                "itemId" => $refeicao->itemId,
                                                "selecionado" => $refeicao->selecionado,
                                                "descricao" => $refeicao->descricao,
                                                "item" => $refeicao->item,
                                                "error" => $refeicao->error
                                            ]
                                        ]
                                    ],JSON_UNESCAPED_UNICODE)
                                ]
                            );


                        });
                    }

                    $data_processavel->addDay();
                }


                $output->writeln("Isto é um teste");
            }
        }


    }


}