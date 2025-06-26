<?php

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueMessageCliConfigurationTransfer;

interface QueueMessageViewerInterface
{
    public function listMessages(QueueMessageCliConfigurationTransfer $configurationTransfer);
}
