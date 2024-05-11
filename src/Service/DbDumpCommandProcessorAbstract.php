<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;

abstract class DbDumpCommandProcessorAbstract
{
    protected OutputInterface $output;

    protected function output(string $str): void
    {
        if (!($this->output instanceof OutputInterface)) {
            return;
        }

        $this->output->writeln($str);
    }
}
