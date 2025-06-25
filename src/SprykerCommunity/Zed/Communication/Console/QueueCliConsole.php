<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueCliConsole extends Console
{
    const NAME = 'queue:process';

    protected function configure(): void
    {
        $this
            ->setName(static::NAME)
            ->setDescription('Advanced processing command queue messages.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        echo "Queue CLI works";

        return static::CODE_SUCCESS;
    }
}
