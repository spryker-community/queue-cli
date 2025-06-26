<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business;

use Generated\Shared\Transfer\QueueMessageMoveConfigurationTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerCommunity\Zed\QueueCli\Business\QueueCliBusinessFactory getFactory()
 */
class QueueCliFacade extends AbstractFacade implements QueueCliFacadeInterface
{
    public function moveMessages(QueueMessageMoveConfigurationTransfer $configurationTransfer): int
    {
        return $this->getFactory()->createQueueMessageMover()->moveMessages($configurationTransfer);
    }
}
