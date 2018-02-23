<?php

namespace AutoUFSM\Command;


use GuzzleHttp\Client;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GetToken extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this
            ->setName("get-token")
            ->setDescription("Requisita tokens de acesso para um usuário");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper("question");

        $matricula_question = new Question("Matricula: ");

        $senha_question = new Question("Senha: ");
        $senha_question->setHidden(true)->setHiddenFallback(false);

        $matricula = $helper->ask($input, $output, $matricula_question);
        $senha = $helper->ask($input, $output, $senha_question);

        $device_id = substr(md5(bin2hex(random_bytes(50))),-16);

        $client = new Client();

        $response = $client->request("POST","https://portal.ufsm.br/mobile/webservice/generateToken",[
            "headers" => [
                "Content-Type" => "application/json; charset=utf-8",
                "Connection" => "Keep-Alive",
                "Accept-Encoding" => "gzip",
                "User-Agent" => "okhttp/3.9.0"
            ],
            "json" => [
                "appName" => "UFSMDigital",
                "deviceId" => $device_id,
                "deviceInfo" => "motorola XT1097 android: 6.0",
                "login" => $matricula,
                "senha" => $senha
            ]

        ]);

        $response_json = json_decode((string)$response->getBody());

        if ($response_json->error) {
            $errOutput->writeln($response_json->mensagem);
        } else {
            $output->writeln(sprintf("Access Token: %s",$response_json->token));
            $output->writeln(sprintf("Device Id: %s",$device_id));
        }


    }


}