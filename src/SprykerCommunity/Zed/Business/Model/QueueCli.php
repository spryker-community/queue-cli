<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\Business\Model;

use Spryker\Client\RabbitMq\RabbitMqClientInterface;

class QueueCli implements QueueCliInterface
{
    protected RabbitMqClientInterface $rabbitMqClient;

    public function __construct(RabbitMqClientInterface $rabbitMqClient)
    {
        $this->rabbitMqClient = $rabbitMqClient;
    }
}

