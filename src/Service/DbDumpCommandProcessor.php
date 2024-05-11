<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\DumpFile;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\DbDumperInterface;
use App\Service\CloudApiClientInterface;

class DbDumpCommandProcessor extends DbDumpCommandProcessorAbstract implements DbDumpCommandProcessorInterface
{
    private DbDumperInterface $dbDumper;
    private CloudApiClientInterface $cloudApiClient;

    public function __construct(
        DbDumperInterface $dbDumper,
        CloudApiClientInterface $cloudApiClient
    ) {
        $this->dbDumper = $dbDumper;
        $this->cloudApiClient = $cloudApiClient;
    }

    public function execute(OutputInterface $output): bool
    {
        $dumpFile = $this->dbDumper
            ->setOutput($output)
            ->dump()
        ;

        if(!($dumpFile instanceof DumpFile)) {
            $this->output('Dump process failed. Stopping the command.');

            return false;
        }

        if ($_ENV['UPLOAD_TO_CLOUD']) {
            $result = $this->cloudApiClient
                ->setDumpFile($dumpFile)
                ->setOutput($output)
                ->upload()
            ;
        }

        return $result;
    }
}
