<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Spryker\Client\RabbitMq\RabbitMqClientInterface;

class QueueMessageMover implements QueueMessageMoverInterface
{
    protected RabbitMqClientInterface $rabbitMqClient;

    public function __construct(RabbitMqClientInterface $rabbitMqClient)
    {
        $this->rabbitMqClient = $rabbitMqClient;
    }

    public function moveMessages(string $sourceQueueName, string $targetQueueName, int $chunkSize): void
    {
        $queueAdapter = $this->rabbitMqClient->createQueueAdapter();

        $queueAdapter->createQueue($targetQueueName, ['durable' => true]);

        while (true) {
            $messages = $queueAdapter->receiveMessages($sourceQueueName, $chunkSize);

            if (count($messages) === 0) {
                break;
            }

            $messagesToSend = [];
            foreach ($messages as $receivedMessage) {
                $queueSendMessageTransfer = $receivedMessage->getQueueMessage();
                if ($queueSendMessageTransfer) {
                    $messagesToSend[] = $queueSendMessageTransfer;
                }
            }

            $queueAdapter->sendMessages($targetQueueName, $messagesToSend);

            foreach ($messages as $receivedMessage) {
                $queueAdapter->acknowledge($receivedMessage);
            }

            if ($chunkSize > 0 && count($messages) < $chunkSize) {
                break;
            }
        }
    }
}
