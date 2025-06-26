<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business;

use Generated\Shared\Transfer\QueueMessageMoveConfigurationTransfer;

interface QueueCliFacadeInterface
{
    public function moveMessages(QueueMessageMoveConfigurationTransfer $configurationTransfer): int;

    public function listMessages(QueueMessageMoveConfigurationTransfer $configuration);
}
