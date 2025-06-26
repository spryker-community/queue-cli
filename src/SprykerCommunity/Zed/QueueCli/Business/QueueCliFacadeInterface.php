<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business;

use Generated\Shared\Transfer\QueueMessageCliConfigurationTransfer;

interface QueueCliFacadeInterface
{
    public function moveMessages(QueueMessageCliConfigurationTransfer $configurationTransfer): int;

    public function listMessages(QueueMessageCliConfigurationTransfer $configuration);
}
