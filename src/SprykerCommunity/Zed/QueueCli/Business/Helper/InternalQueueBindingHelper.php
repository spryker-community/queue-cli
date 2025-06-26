<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Helper;

use Generated\Shared\Transfer\RabbitMqOptionTransfer;
use Spryker\Client\RabbitMq\Model\Connection\Connection;

class InternalQueueBindingHelper
{
    /**
     * @param \Spryker\Client\RabbitMq\Model\Connection\Connection $connection
     * @param \Generated\Shared\Transfer\RabbitMqOptionTransfer $bindingTransfer
     * @param string $queueName
     *
     * @return void
     */
    public function createQueueAndBind(Connection $connection, RabbitMqOptionTransfer $bindingTransfer, string $queueName): void
    {
        $reflection = new \ReflectionMethod(Connection::class, 'createQueueAndBind');
        $reflection->setAccessible(true);
        $reflection->invoke($connection, $bindingTransfer, $queueName);
    }
}
