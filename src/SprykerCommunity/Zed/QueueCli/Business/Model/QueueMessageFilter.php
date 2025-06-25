<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;

final readonly class QueueMessageFilter implements QueueMessageFilterInterface
{
    public function matches(QueueReceiveMessageTransfer $message, string $needle): bool
    {
        $body = $message->getQueueMessage()?->getBody();

        return str_contains($body, $needle);
    }
}
