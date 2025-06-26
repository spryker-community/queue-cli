<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueMessageMoveConfigurationTransfer;

interface QueueMessageMoverInterface
{
    public function moveMessages(QueueMessageMoveConfigurationTransfer $configurationTransfer): int;
}

