<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class QueueCliConsole extends Console
{
    public const COMMAND_NAME = 'queue:messages:move';
    private const DESCRIPTION = 'Move messages from one queue to another.';

    private const ARGUMENT_SOURCE_QUEUE = 'source-queue';
    private const ARGUMENT_TARGET_QUEUE = 'target-queue';
    private const OPTION_CHUNK_SIZE = 'chunk-size';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(self::DESCRIPTION)
            ->addArgument(self::ARGUMENT_SOURCE_QUEUE, InputArgument::REQUIRED, 'Source queue name')
            ->addArgument(self::ARGUMENT_TARGET_QUEUE, InputArgument::REQUIRED, 'Target queue name')
            ->addOption(self::OPTION_CHUNK_SIZE, 'c', InputOption::VALUE_OPTIONAL, 'Number of messages to process in one batch.', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceQueueName = $input->getArgument(self::ARGUMENT_SOURCE_QUEUE);
        $targetQueueName = $input->getArgument(self::ARGUMENT_TARGET_QUEUE);
        $chunkSize = (int)$input->getOption(self::OPTION_CHUNK_SIZE);

        $this->getFacade()->moveMessages($sourceQueueName, $targetQueueName, $chunkSize);

        $output->writeln(
            sprintf(
                'Successfully moved messages from "%s" to "%s".',
                $sourceQueueName,
                $targetQueueName
            )
        );

        return static::CODE_SUCCESS;
    }
}
