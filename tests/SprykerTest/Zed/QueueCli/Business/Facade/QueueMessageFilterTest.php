<?php

declare(strict_types=1);

namespace tests\SprykerTest\Zed\QueueCli\Business\Facade;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use PHPUnit\Framework\TestCase;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageFilter;

final class QueueMessageFilterTest extends TestCase
{
    public function testFilterMatchingStringReturnsTrue(): void
    {
        // Arrange
        $filter = new QueueMessageFilter();

        $message = (new QueueReceiveMessageTransfer())
            ->setQueueMessage(
                (new QueueSendMessageTransfer())
                    ->setBody('example string')
            );

        // Act
        $actual = $filter->matches($message, 'example');


        // Assert
        self::assertTrue($actual);
    }

    public function testFilterNotMatchingStringReturnsFalse(): void
    {
        // Arrange
        $filter = new QueueMessageFilter();

        $message = (new QueueReceiveMessageTransfer())
            ->setQueueMessage(
                (new QueueSendMessageTransfer())
                    ->setBody('example string')
            );

        // Act
        $actual = $filter->matches($message, 'test');

        // Assert
        self::assertFalse($actual);
    }
}
