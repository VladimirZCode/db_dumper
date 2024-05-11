<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\DumpFile;

interface CloudApiClientInterface
{
    public function upload(): bool;
    public function setOutput(OutputInterface $output): CloudApiClientInterface;
    public function setDumpFile(DumpFile $dumpFile): CloudApiClientInterface;
}
