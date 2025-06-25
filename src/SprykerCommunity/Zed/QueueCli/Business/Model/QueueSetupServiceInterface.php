<?php

namespace SprykerCommunity\Zed\QueueCli\Business\Model;

interface QueueSetupServiceInterface
{
    public function setupTargetQueue(string $queueName): void;
}
