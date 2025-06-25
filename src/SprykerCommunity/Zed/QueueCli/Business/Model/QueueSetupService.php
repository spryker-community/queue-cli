<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\RabbitMqOptionTransfer;
use Spryker\Client\RabbitMq\Model\Connection\Connection;
use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;
use SprykerCommunity\Zed\QueueCli\Business\Helper\InternalQueueBindingHelper;

final readonly class QueueSetupService implements QueueSetupServiceInterface
{
    public function __construct(
        private RabbitMqClientInterface $rabbitMqClient,
        private QueueEstablishmentHelperInterface $queueEstablishmentHelper,
        private InternalQueueBindingHelper $internalQueueBindingHelper
    ) {
    }

    public function setupTargetQueue(string $queueName): void
    {
        $queueAdapter = $this->rabbitMqClient->createQueueAdapter();
        $queueBindingTransfer = $this->createQueueOptionTransfer($queueName);

        $rabbitMqOptionTransfer = (new RabbitMqOptionTransfer())
            ->setQueueName($queueName)
            ->setDurable(true)
            ->setType('direct')
            ->setDeclarationType(Connection::RABBIT_MQ_EXCHANGE)
            ->addBindingQueueItem($queueBindingTransfer);

        $queueAdapter->createQueue(
            $queueName,
            ['rabbitMqConsumerOption' => $rabbitMqOptionTransfer]
        );

        $this->queueEstablishmentHelper->createExchange(
            $this->rabbitMqClient->getConnection()->getChannel(),
            $rabbitMqOptionTransfer
        );

        $this->internalQueueBindingHelper->createQueueAndBind(
            $this->rabbitMqClient->getConnection(),
            $queueBindingTransfer,
            $queueName
        );
    }

    protected function createQueueOptionTransfer(string $queueName, string $routingKey = ''): RabbitMqOptionTransfer
    {
        return (new RabbitMqOptionTransfer())
            ->setQueueName($queueName)
            ->setDurable(true)
            ->setNoWait(false)
            ->addRoutingKey($routingKey);
    }
}
