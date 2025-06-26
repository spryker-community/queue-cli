<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Communication\Console;

use Generated\Shared\Transfer\QueueMessageMoveConfigurationTransfer;
use SprykerCommunity\Zed\QueueCli\Business\QueueCliFacadeInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputOption;

/**
 * @method QueueCliFacadeInterface getFacade()
 */
class QueueMessagesListConsole extends Console
{
    protected const COMMAND_NAME = 'queue:messages:list';
    protected const DESCRIPTION = 'List messages from a specific queue with optional filters.';

    private const ARGUMENT_SOURCE_QUEUE = 'source-queue';
    private const OPTION_FILTER = 'filter';
    private const OPTION_LIMIT = 'limit';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(static::DESCRIPTION)
            ->addArgument(self::ARGUMENT_SOURCE_QUEUE, InputArgument::REQUIRED, 'Source queue name')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceQueueName = $input->getArgument(self::ARGUMENT_SOURCE_QUEUE);
        $filter = $input->getOption('filter') ?? '';
        $limit = (int)$input->getOption('limit') ?: null;

        $configuration = (new QueueMessageMoveConfigurationTransfer())
            ->setSourceQueue($sourceQueueName)
            ->setLimit($limit)
            ->setFilter($filter)
            ->setKeep(true);

        $messages = $this->getFacade()->listMessages($configuration);

        foreach ($messages as $message) {
            $output->writeln($message->getBody());
        }

        return static::CODE_SUCCESS;
    }
}
