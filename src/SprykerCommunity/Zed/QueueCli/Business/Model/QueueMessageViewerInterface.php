<?php

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueMessageMoveConfigurationTransfer;

interface QueueMessageViewerInterface
{
    public function listMessages(QueueMessageMoveConfigurationTransfer $configurationTransfer);
}
