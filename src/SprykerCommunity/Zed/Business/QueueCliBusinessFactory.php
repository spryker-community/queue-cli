<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\Business;

use Spryker\Client\RabbitMq\RabbitMqClientInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerCommunity\Zed\QueueCliDependencyProvider;

class QueueCliBusinessFactory extends AbstractBusinessFactory
{
    protected function getRabbitMqClient(): RabbitMqClientInterface
    {
        return $this->getProvidedDependency(QueueCliDependencyProvider::CLIENT_RABBITMQ);
    }
}

