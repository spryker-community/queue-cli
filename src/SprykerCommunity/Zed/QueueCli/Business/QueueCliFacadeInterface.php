<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\Business;

interface QueueCliFacadeInterface
{
    public function moveMessages(string $sourceQueueName, string $targetQueueName, int $chunkSize): void;
}
