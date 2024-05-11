<?php declare(strict_types=1);

namespace App\Command;

use App\Service\DbDumpCommandProcessorInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;

class DbDumpCommand extends Command
{
    protected static $defaultName = "dbdumper";

    private DbDumpCommandProcessorInterface $commandProcessor;

    public function __construct(
        DbDumpCommandProcessorInterface $commandProcessor
    ) {
        $this->commandProcessor = $commandProcessor;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Dumps a database based on the command provided through a configuration and uploads the dump to a cloud.')
            ->setHelp('Dumps a database and uploads the dump to a cloud.')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->loadEnvironmentVariables();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf("\"%s\" command started.", static::getDefaultName()));

        $result = $this->commandProcessor->execute($output);

        if (!$result) {
            $output->writeln(sprintf("\"%s\" command was not finished successfully.", static::getDefaultName()));

            return self::FAILURE;
        }

        $output->writeln(sprintf("\"%s\" command finished.", static::getDefaultName()));

        return self::SUCCESS;
    }

    protected function loadEnvironmentVariables()
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.env');
    }
}
