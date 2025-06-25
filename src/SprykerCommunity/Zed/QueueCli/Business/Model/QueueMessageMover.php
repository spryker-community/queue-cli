<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer;
use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;
use SprykerCommunity\Zed\QueueCli\Business\Helper\InternalQueueBindingHelper;

class QueueMessageMover implements QueueMessageMoverInterface
{
    private const CONSUMER_TAG = 'queue-cli';

    /**
     * @param QueueMessageFilterInterface[] $queueMessageFilters
     */
    public function __construct(
        protected RabbitMqClientInterface $rabbitMqClient,
        protected QueueEstablishmentHelperInterface $queueEstablishmentHelper,
        protected InternalQueueBindingHelper $internalQueueBindingHelper,
        protected QueueSetupServiceInterface $queueSetupService,
        protected array $queueMessageFilters
    ) {
    }

    public function moveMessages(string $sourceQueueName, string $targetQueueName, int $chunkSize, string $filter, ?int $limit = null): int
    {
        $queueAdapter = $this->rabbitMqClient->createQueueAdapter();

        $this->queueSetupService->setupTargetQueue($targetQueueName);

        return $this->processMessages(
            $queueAdapter,
            $sourceQueueName,
            $targetQueueName,
            $chunkSize,
            $filter,
            $limit
        );
    }

    protected function processMessages(
        $queueAdapter,
        string $sourceQueueName,
        string $targetQueueName,
        int $chunkSize,
        string $filterString,
        ?int $limit
    ): int {
        $consumerOptions = $this->createConsumerOptions($sourceQueueName);

        $processedCount = 0;

        while ($limit === null || $processedCount < $limit) {
            $messages = $queueAdapter->receiveMessages(
                $sourceQueueName,
                $chunkSize,
                ['rabbitmq' => $consumerOptions]
            );

            if (count($messages) === 0) {
                break;
            }

            $messagesToSend = [];
            $messagesToAcknowledge = [];

            foreach ($messages as $receivedMessage) {
                if (!$this->applyFilter($receivedMessage, $filterString)) {
                    continue;
                }

                $queueSendMessageTransfer = $receivedMessage->getQueueMessage();
                if ($queueSendMessageTransfer) {
                    $messagesToSend[] = $queueSendMessageTransfer;
                    $messagesToAcknowledge[] = $receivedMessage;
                    $processedCount++;
                }
            }

            if (!empty($messagesToSend)) {
                $queueAdapter->sendMessages($targetQueueName, $messagesToSend);
            }

            foreach ($messagesToAcknowledge as $receivedMessage) {
                $queueAdapter->acknowledge($receivedMessage);
            }

            if ($chunkSize > 0 && count($messages) < $chunkSize) {
                break;
            }
        }

        return $processedCount;
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

    private function applyFilter(QueueReceiveMessageTransfer $receivedMessage, string $filterString): bool
    {
        foreach ($this->queueMessageFilters as $filter) {
            if ($filter->matches($receivedMessage, $filterString)) {
                return true;
            }
        }

        return false;
    }
}
