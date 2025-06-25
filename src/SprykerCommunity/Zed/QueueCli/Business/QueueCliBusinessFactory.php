<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business;

use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelper;
use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMover;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMoverInterface;
use SprykerCommunity\Zed\QueueCli\QueueCliDependencyProvider;

class QueueCliBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface
     */
    public function createQueueEstablishmentHelper(): QueueEstablishmentHelperInterface
    {
        return new QueueEstablishmentHelper();
    }

    /**
     * @return \SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMoverInterface
     */
    public function createQueueMessageMover(): QueueMessageMoverInterface
    {
        return new QueueMessageMover(
            $this->getRabbitMqClient(),
            $this->createQueueEstablishmentHelper()
        );
    }

    /**
     * @throws \Spryker\Zed\Kernel\Exception\Container\ContainerKeyNotFoundException
     * @return \Spryker\Client\RabbitMq\RabbitMqClientInterface
     */
    protected function getRabbitMqClient(): RabbitMqClientInterface
    {
        return $this->getProvidedDependency(QueueCliDependencyProvider::CLIENT_RABBITMQ);
    }
}
