<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerCommunity\Zed\QueueCli\Business\QueueCliBusinessFactory getFactory()
 */
class QueueCliFacade extends AbstractFacade implements QueueCliFacadeInterface
{
    public function moveMessages(string $sourceQueueName, string $targetQueueName, int $chunkSize): void
    {
        $this->getFactory()->createQueueMessageMover()->moveMessages($sourceQueueName, $targetQueueName, $chunkSize);
    }
}
