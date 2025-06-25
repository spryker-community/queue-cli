<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer;
use Generated\Shared\Transfer\RabbitMqOptionTransfer;
use Spryker\Client\RabbitMq\Model\Connection\Connection;
use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;

class QueueMessageMover implements QueueMessageMoverInterface
{

    public function __construct(
        protected RabbitMqClientInterface $rabbitMqClient,
        protected QueueEstablishmentHelperInterface $queueEstablishmentHelper
    ) {
    }

    public function moveMessages(string $sourceQueueName, string $targetQueueName, int $chunkSize): void
    {
        $queueAdapter = $this->rabbitMqClient->createQueueAdapter();

        $queueBindingTransfer = $this->createQueueOptionTransfer($targetQueueName);

        $rabbitMqOptionTransfer = (new RabbitMqOptionTransfer())
            ->setQueueName($targetQueueName)
            ->setDurable(true)
            ->setType('direct')
            ->setDeclarationType(Connection::RABBIT_MQ_EXCHANGE)
            ->addBindingQueueItem($queueBindingTransfer);

        $queueAdapter->createQueue(
            $targetQueueName,
            [
                'rabbitMqConsumerOption' => $rabbitMqOptionTransfer,
            ]
        );

        $this->queueEstablishmentHelper->createExchange(
            $this->rabbitMqClient->getConnection()->getChannel(),
            $rabbitMqOptionTransfer
        );

        $connection = $this->rabbitMqClient->getConnection();
        $reflectionMethod = new \ReflectionMethod(Connection::class, 'createQueueAndBind');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke(
            $connection,
            $queueBindingTransfer,
            $targetQueueName
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

    /**
     * @param string $queueName
     * @param string $routingKey
     *
     * @return \Generated\Shared\Transfer\RabbitMqOptionTransfer
     */
    protected function createQueueOptionTransfer($queueName, $routingKey = '')
    {
        $queueOptionTransfer = new RabbitMqOptionTransfer();
        $queueOptionTransfer
            ->setQueueName($queueName)
            ->setDurable(true)
            ->setNoWait(false)
            ->addRoutingKey($routingKey);

        return $queueOptionTransfer;
    }
}
