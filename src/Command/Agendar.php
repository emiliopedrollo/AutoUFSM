<?php

namespace AutoUFSM\Command;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Collection;

class Agendar extends Command {


    protected function configure() {
        parent::configure();
        $this
            ->setName("agendar")
            ->setDescription("Realiza agendamentos")
            ->addOption("offset","o",InputOption::VALUE_OPTIONAL,
                "Number of days to skip while making schedules",2);
    }


    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::execute($input,$output);

        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $agora = Carbon::now();

        $offset = $input->getOption('offset');

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
                $data_processavel = (clone $agora)->addDays($offset)->startOfDay();

                while($data_processavel->lessThanOrEqualTo($data_maxima)) {

                    $dia = $data_processavel->formatLocalized("%a");

                    if (isset($user['Agendamentos'][$dia])){
                        $refeicoes = new Collection($user['Agendamentos'][$dia]);
                        $refeicoes->each(function($refeicao_dados,$refeicao_text) use ($data_processavel, $user, $client, $restaurantes, $tipos_refeicao, $output, $errOutput) {

                            $refeicao = $tipos_refeicao->where('descricao','=',$refeicao_text)->first();

                            $restaurante_text = $refeicao_dados['Restaurante'];
                            $restaurante = $restaurantes->where('nome','=',$restaurante_text)->first();

                            $response = $client->request('post','https://portal.ufsm.br/mobile/webservice/ru/agendaRefeicoes',
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

                            $response_json = json_decode((string)$response->getBody())[0];

                            if ($response_json->error || !$response_json->sucesso) {
                                $errOutput->writeln(sprintf("Não foi possível agendar %s para %s em %s: %s",
                                    $response_json->tipoRefeicao,
                                    Carbon::parse($response_json->dataRefAgendada)->formatLocalized("%x"),
                                    $restaurante_text,
                                    $response_json->impedimento));
                            } else {
                                $output->writeln(sprintf("%s gendado com sucesso para o dia %s em %s",
                                    $response_json->tipoRefeicao,
                                    Carbon::parse($response_json->dataRefAgendada)->formatLocalized("%x"),
                                    $restaurante_text));
                            }

                        });
                    }

                    $data_processavel->addDay();
                }
            }
        }


    }


}