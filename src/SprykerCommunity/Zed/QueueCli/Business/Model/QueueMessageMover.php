<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueMessageCliConfigurationTransfer;
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

    public function moveMessages(QueueMessageCliConfigurationTransfer $configurationTransfer): int
    {
        $this->queueSetupService->setupTargetQueue($configurationTransfer->getTargetQueue());

        return $this->processMessages($configurationTransfer);
    }

    protected function processMessages(QueueMessageCliConfigurationTransfer $configurationTransfer): int
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
            $messagesToAcknowledge = [];

            foreach ($messages as $receivedMessage) {
                if (!$this->applyFilter($receivedMessage, $configurationTransfer->getFilter())) {
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
                $queueAdapter->sendMessages($configurationTransfer->getTargetQueue(), $messagesToSend);
            }

            foreach ($messagesToAcknowledge as $receivedMessage) {
                if (!$configurationTransfer->getKeep()) {
                    $queueAdapter->acknowledge($receivedMessage);
                }
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
