<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer;
use Generated\Shared\Transfer\RabbitMqOptionTransfer;
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

        $queueBindingTransfer = (new RabbitMqOptionTransfer())
            ->setQueueName($targetQueueName)
            ->setDurable(true)
            ->setNoWait(false)
            ->addRoutingKey($targetQueueName);

        $rabbitMqOptionTransfer = (new RabbitMqOptionTransfer())
            ->setQueueName($targetQueueName)
            ->setDurable(true)
            ->setType('direct')
            ->setDeclarationType('exchange')
            ->addBindingQueueItem($queueBindingTransfer);

        $queueAdapter->createQueue(
            $targetQueueName,
            [
                'rabbitMqConsumerOption' => $rabbitMqOptionTransfer,
            ]
        );

        $consumerOptions = $this->createConsumerOptions($sourceQueueName);

        while (true) {
            $messages = $queueAdapter->receiveMessages(
                $sourceQueueName,
                $chunkSize,
                [
                    'rabbitmq' => $consumerOptions,
                ]
            );

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

    /**
     * @param string $queueName
     *
     * @return \Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer
     */
    protected function createConsumerOptions(string $queueName): RabbitMqConsumerOptionTransfer
    {
        return (new RabbitMqConsumerOptionTransfer())
            ->setQueueName($queueName)
            ->setConsumerTag('queue-cli')
            ->setNoAck(false)
            ->setNoLocal(false)
            ->setConsumerExclusive(false)
            ->setNoWait(false);
    }
}
