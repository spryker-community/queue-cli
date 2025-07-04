<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueMessageCliConfigurationTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;

final class QueueMessageViewer implements QueueMessageViewerInterface
{
    private const CONSUMER_TAG = 'queue-cli';

    /**
     * @param QueueMessageFilterInterface[] $queueMessageFilters
     */
    public function __construct(
        protected RabbitMqClientInterface $rabbitMqClient,
        protected array $queueMessageFilters
    ) {
    }

    /**
     * @return QueueSendMessageTransfer[]
     */
    public function listMessages(QueueMessageCliConfigurationTransfer $configurationTransfer): array
    {
        $queueAdapter = $this->rabbitMqClient->createQueueAdapter();

        $consumerOptions = $this->createConsumerOptions($configurationTransfer->getSourceQueue());

        $limit = $configurationTransfer->getLimit();
        $chunkSize = $configurationTransfer->getChunkSize();

        $processedCount = 0;

        while ($limit === null || $processedCount < $limit) {
            $messages = $queueAdapter->receiveMessages(
                $configurationTransfer->getSourceQueue(),
                $chunkSize,
                ['rabbitmq' => $consumerOptions]
            );

            if (count($messages) === 0) {
                break;
            }

            $messagesToSend = [];

            foreach ($messages as $receivedMessage) {
                if (!$this->applyFilter($receivedMessage, $configurationTransfer->getFilter())) {
                    continue;
                }

                $queueSendMessageTransfer = $receivedMessage->getQueueMessage();
                if ($queueSendMessageTransfer) {
                    $messagesToSend[] = $queueSendMessageTransfer;
                    $processedCount++;
                }
            }

            if ($chunkSize > 0 && count($messages) < $chunkSize) {
                break;
            }
        }

        return $messagesToSend;
    }

    protected function applyFilter(QueueReceiveMessageTransfer $receivedMessage, string $filterString): bool
    {
        foreach ($this->queueMessageFilters as $filter) {
            if ($filter->matches($receivedMessage, $filterString)) {
                return true;
            }
        }

        return false;
    }

    protected function createConsumerOptions(string $queueName): RabbitMqConsumerOptionTransfer
    {
        return (new RabbitMqConsumerOptionTransfer())
            ->setQueueName($queueName)
            ->setConsumerTag(self::CONSUMER_TAG)
            ->setNoAck(false)
            ->setNoLocal(false)
            ->setConsumerExclusive(false)
            ->setNoWait(false);
    }
}
