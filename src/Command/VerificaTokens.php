<?php

namespace AutoUFSM\Command;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerificaTokens extends Command
{



    protected function configure() {
        parent::configure();
        $this
            ->setName("verifica-tokens")
            ->setDescription("Verifica tokens do arquivo de carga");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $user_index = 0;

        foreach ($this->load as $user) {

            $client = new Client;

            try {
                $response = $client->request("POST", "https://portal.ufsm.br/mobile/webservice/vinculos", [
                    "headers" => array_merge($this->getAPIHeaders($user), [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ]),
                    "form_params" => [
                        "buscaFoto" => "false"
                    ]
                ]);
            } catch (GuzzleException $e) {
                $errOutput->writeln(sprintf("(%s) Não foi possível verificar token: %s",
                    $user["User"],
                    $e->getMessage()
                ));
                continue;
            }

            $response_json = json_decode((string)$response->getBody());

            $output->writeln(sprintf("Usuário %d (%s): %s",
                $user_index++, $user["User"],$response_json->mensagem));

        }

    }


}