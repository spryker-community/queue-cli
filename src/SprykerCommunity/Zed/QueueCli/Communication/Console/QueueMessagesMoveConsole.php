<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Communication\Console;

use Generated\Shared\Transfer\QueueMessageMoveConfigurationTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerCommunity\Zed\QueueCli\Business\QueueCliFacadeInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method QueueCliFacadeInterface getFacade()
 */
class QueueMessagesMoveConsole extends Console
{
    public const COMMAND_NAME = 'queue:messages:move';
    private const DESCRIPTION = 'Move messages from one queue to another.';

    private const ARGUMENT_SOURCE_QUEUE = 'source-queue';
    private const ARGUMENT_TARGET_QUEUE = 'target-queue';
    private const OPTION_CHUNK_SIZE = 'chunk-size';
    private const OPTION_FILTER = 'filter';
    private const OPTION_LIMIT = 'limit';
    private const OPTION_KEEP_MESSAGE = 'keep';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(self::DESCRIPTION)
            ->addArgument(self::ARGUMENT_SOURCE_QUEUE, InputArgument::REQUIRED, 'Source queue name')
            ->addArgument(self::ARGUMENT_TARGET_QUEUE, InputArgument::REQUIRED, 'Target queue name')
            ->addOption(
                self::OPTION_CHUNK_SIZE,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Number of messages to process in one batch.',
                100
            )
            ->addOption(
                self::OPTION_FILTER,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Pattern (string) for message body to be match.'
            )
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit of how many messages will be processed max.'
            )
            ->addOption(
                self::OPTION_KEEP_MESSAGE,
                'k',
                InputOption::VALUE_NONE,
                'If set to 1, messages will be kept in source queue.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configurationTransfer = $this->createConfigurationTransfer($input);

        $processedCount = $this->getFacade()->moveMessages($configurationTransfer);

        $output->writeln(
            sprintf(
                'Successfully moved %s messages from "%s" to "%s".',
                $processedCount,
                $configurationTransfer->getSourceQueue(),
                $configurationTransfer->getTargetQueue()
            )
        );

        return static::CODE_SUCCESS;
    }

    private function createConfigurationTransfer(InputInterface $input): QueueMessageMoveConfigurationTransfer
    {
        $sourceQueueName = $input->getArgument(self::ARGUMENT_SOURCE_QUEUE);
        $targetQueueName = $input->getArgument(self::ARGUMENT_TARGET_QUEUE);
        $chunkSize = (int)$input->getOption(self::OPTION_CHUNK_SIZE);
        $filter = (string)$input->getOption(self::OPTION_FILTER);
        $limit = $input->getOption(self::OPTION_LIMIT) ?? null;
        $keep = (bool)$input->getOption(self::OPTION_KEEP_MESSAGE);

        return (new QueueMessageMoveConfigurationTransfer())
            ->setSourceQueue($sourceQueueName)
            ->setTargetQueue($targetQueueName)
            ->setChunkSize($chunkSize)
            ->setFilter($filter)
            ->setKeep($keep)
            ->setLimit($limit);
    }
}
