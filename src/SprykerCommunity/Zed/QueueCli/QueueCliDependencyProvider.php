<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class QueueCliDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_RABBITMQ = 'CLIENT_RABBITMQ';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @throws \Spryker\Service\Container\Exception\FrozenServiceException
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addRabbitMqClient($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @throws \Spryker\Service\Container\Exception\FrozenServiceException
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addRabbitMqClient(Container $container): Container
    {
        $container->set(static::CLIENT_RABBITMQ, function (Container $container) {
            return $container->getLocator()->rabbitMq()->client();
        });

        return $container;
    }
}

