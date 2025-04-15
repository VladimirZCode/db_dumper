<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\DumpFile;
use DateTimeImmutable;
use Exception;

class DbDumper implements DbDumperInterface
{
    private OutputInterface $output;
    private string $currentDate;

    public function setOutput(OutputInterface $output): DbDumperInterface
    {
        $this->output = $output;
        return $this;
    }

    public function dump(): DumpFile
    {
        if (!empty($_ENV['TEST_DB_CONNECTION'])) {
            if (!$this->testDbConnection()) {
                throw new Exception("No connection to the database!");
            }
        }

        $fileName = sprintf($_ENV['DB_DUMP_FILE_NAME_TPL'], $this->getCurrentDate());
        $dumpFile = new DumpFile($_ENV['DB_DUMP_FILE_PATH'], $fileName);

        $commands = $this->getCommandsArray($_ENV['DB_DUMP_COMMANDS_LIST'], $dumpFile->getFullPath());

        $this->output("Database dump started...");

        foreach($commands as $command) {
            exec($command);
        }

        $this->output("Database dump finished.");

        return $dumpFile;
    }

    protected function output(string $str): void
    {
        if (!($this->output instanceof OutputInterface)) {
            return;
        }

        $this->output->writeln($str);
    }

    private function testDbConnection(): bool
    {
        $this->output(sprintf("Test database connection."));

        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $user = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASSWORD'];
        $db = $_ENV['DB_NAME'];
        $charset = $_ENV['DB_CHARSET'];

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $this->output(sprintf("dsn: \"%s\"", $dsn));

        try {
            $pdo = new \PDO($dsn, $user, $password);

            if (!$pdo) {
                $this->output("Trying to connect to the $db database. No connection!");

                return false;
            }
        } catch (\PDOException $e) {
            $this->output("PDOException: " . $e->getMessage());
            throw new Exception("PDOException: " . $e->getMessage());
        }

        return true;
    }

    private function getCommandsArray(
        string $commandsListStr,
        string $valueToExchange,
        string $specifier = '%s'
    ): array {
        $commandsListStr = $this->replaceEnvVariables($commandsListStr);

        $specifiersNumber = substr_count($commandsListStr, $specifier);
        $strValues = [];
        for($i = 0; $i < $specifiersNumber; $i++) {
            $strValues[] = $valueToExchange;
        }
        $commandsListStr = sprintf($commandsListStr, ...$strValues);

        return explode(';', $commandsListStr);
    }

    private function replaceEnvVariables(string $str): string
    {
        return preg_replace_callback(
            '/\$([A-Z_]+)/',
            function ($matches) {
                return $this->getVariableValue($matches[1]);
            },
            $str
        );
    }

    private function getVariableValue(string $name): string
    {
        $specialVariables = [
            'CURRENT_DATE' => fn() => $this->getCurrentDate(),
        ];

        if (isset($specialVariables[$name])) {
            return $specialVariables[$name]();
        }

        return $_ENV[$name] ?? $name;
    }

    private function getCurrentDate(): string
    {
        if (empty($this->currentDate)) {
            $this->currentDate = (new DateTimeImmutable())->format('Ymd_His');
        }

        return $this->currentDate;
    }
}
