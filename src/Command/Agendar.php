<?php

namespace AutoUFSM\Command;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Collection;

class Agendar extends Command {


    protected $load;

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

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->load = $input->getOption('load');
        if ($this->load == null) {

            if (\Phar::running()){
                $this->load = Yaml::parseFile(getcwd() . "/load.yml");
            } else {
                $this->load = Yaml::parseFile(__DIR__ . "/../../load.yml");
            }

        }

    }

    protected function execute(InputInterface $input, OutputInterface $output) {


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