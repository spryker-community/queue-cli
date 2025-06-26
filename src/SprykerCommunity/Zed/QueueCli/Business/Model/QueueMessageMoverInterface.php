<?php

declare(strict_types=1);

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

interface QueueMessageMoverInterface
{
    public function moveMessages(string $sourceQueueName, string $targetQueueName, int $chunkSize, string $filter, bool $keep, ?int $limit): int;
}

