<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerCommunity\Zed\Business\QueueCliBusinessFactory getFactory()
 */
class QueueCliFacade extends AbstractFacade implements QueueCliFacadeInterface
{
    public function moveMessages(string $sourceQueueName, string $targetQueueName, int $chunkSize): void
    {
        $this->getFactory()->createQueueMessageMover()->moveMessages($sourceQueueName, $targetQueueName, $chunkSize);
    }
}
