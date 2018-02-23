<?php

namespace AutoUFSM\Command;


use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
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

        $user_index = 0;

        foreach ($this->load as $user) {

            $client = new Client;

            $response = $client->request("POST","https://portal.ufsm.br/mobile/webservice/vinculos",[
                "headers" => array_merge($this->getAPIHeaders($user),[
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]),
                "form_params" => [
                    "buscaFoto" => "false"
                ]
            ]);

            $response_json = json_decode((string)$response->getBody());

            $output->writeln(sprintf("Usuário %d (%s): %s",
                $user_index++, $user["User"],$response_json->mensagem));

        }

    }


}