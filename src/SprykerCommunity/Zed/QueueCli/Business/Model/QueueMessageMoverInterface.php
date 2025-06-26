<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueMessageCliConfigurationTransfer;

interface QueueMessageMoverInterface
{
    public function moveMessages(QueueMessageCliConfigurationTransfer $configurationTransfer): int;
}

