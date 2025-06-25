<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business;

use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelper;
use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerCommunity\Zed\QueueCli\Business\Helper\InternalQueueBindingHelper;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageFilter;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageFilterInterface;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMover;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMoverInterface;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueSetupService;
use SprykerCommunity\Zed\QueueCli\QueueCliDependencyProvider;

class QueueCliBusinessFactory extends AbstractBusinessFactory
{
    public function createQueueEstablishmentHelper(): QueueEstablishmentHelperInterface
    {
        return new QueueEstablishmentHelper();
    }

    public function createQueueMessageMover(): QueueMessageMoverInterface
    {
        return new QueueMessageMover(
            $this->getRabbitMqClient(),
            $this->createQueueEstablishmentHelper(),
            $this->createInternalQueueBindingHelper(),
            $this->createQueueSetupService(),
            $this->getQueueMessageFilters()
        );
    }

    /**
     * @throws \Spryker\Zed\Kernel\Exception\Container\ContainerKeyNotFoundException
     */
    protected function getRabbitMqClient(): RabbitMqClientInterface
    {
        return $this->getProvidedDependency(QueueCliDependencyProvider::CLIENT_RABBITMQ);
    }

    protected function createInternalQueueBindingHelper(): InternalQueueBindingHelper
    {
        return new InternalQueueBindingHelper();
    }

    protected function createQueueSetupService(): QueueSetupService
    {
        return new QueueSetupService(
            $this->getRabbitMqClient(),
            $this->createQueueEstablishmentHelper(),
            $this->createInternalQueueBindingHelper()
        );
    }

    /**
     * @return QueueMessageFilterInterface[]
     */
    private function getQueueMessageFilters(): array
    {
        return [
            new QueueMessageFilter(),
        ];
    }
}
