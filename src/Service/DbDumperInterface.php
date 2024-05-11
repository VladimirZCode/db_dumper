<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\DumpFile;

interface DbDumperInterface
{
    public function dump(): DumpFile;
    public function setOutput(OutputInterface $output): DbDumperInterface;
}
