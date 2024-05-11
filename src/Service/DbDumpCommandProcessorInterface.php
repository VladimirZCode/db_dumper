<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;

interface DbDumpCommandProcessorInterface
{
    public function execute(OutputInterface $output): bool;
}
