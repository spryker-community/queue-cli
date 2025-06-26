<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business;

use Generated\Shared\Transfer\QueueMessageCliConfigurationTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerCommunity\Zed\QueueCli\Business\QueueCliBusinessFactory getFactory()
 */
class QueueCliFacade extends AbstractFacade implements QueueCliFacadeInterface
{
    public function moveMessages(QueueMessageCliConfigurationTransfer $configurationTransfer): int
    {
        return $this->getFactory()->createQueueMessageMover()->moveMessages($configurationTransfer);
    }

    public function listMessages(QueueMessageCliConfigurationTransfer $configuration)
    {
        return $this->getFactory()->createQueueMessageViewer()->listMessages($configuration);
    }

}
