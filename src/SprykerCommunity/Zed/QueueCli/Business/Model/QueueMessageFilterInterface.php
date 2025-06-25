<?php

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;

interface QueueMessageFilterInterface
{
    public function matches(QueueReceiveMessageTransfer $message, string $needle): bool;
}
