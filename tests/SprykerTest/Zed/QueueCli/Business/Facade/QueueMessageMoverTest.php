<?php

namespace SprykerTest\Zed\QueueCli\Business\Model;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use PhpAmqpLib\Channel\AMQPChannel;
use Spryker\Client\Queue\Model\Adapter\AdapterInterface;
use Spryker\Client\RabbitMq\Model\Connection\Connection;
use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMover;

class QueueMessageMoverTest extends Unit
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\RabbitMq\RabbitMqClientInterface
     */
    protected $rabbitMqClientMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface
     */
    protected $queueEstablishmentHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\Queue\Model\Adapter\AdapterInterface
     */
    protected $queueAdapterMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\RabbitMq\Model\Connection\Connection
     */
    protected $connectionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channelMock;

    /**
     * @var \SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMover
     */
    protected $queueMessageMover;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->rabbitMqClientMock = $this->getMockBuilder(RabbitMqClientInterface::class)->getMock();
        $this->queueEstablishmentHelperMock = $this->getMockBuilder(QueueEstablishmentHelperInterface::class)->getMock();
        $this->queueAdapterMock = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $this->connectionMock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->channelMock = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

        $this->rabbitMqClientMock->method('createQueueAdapter')->willReturn($this->queueAdapterMock);
        $this->rabbitMqClientMock->method('getConnection')->willReturn($this->connectionMock);
        $this->connectionMock->method('getChannel')->willReturn($this->channelMock);

        $this->queueMessageMover = new QueueMessageMover(
            $this->rabbitMqClientMock,
            $this->queueEstablishmentHelperMock
        );
    }

    /**
     * @return void
     */
    public function testMoveMessagesMovesSingleMessageSuccessfully(): void
    {
        // Arrange
        $sourceQueueName = 'source-queue';
        $targetQueueName = 'target-queue';
        $chunkSize = 10;

        $messageBody = '{"data":"test"}';
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())->setBody($messageBody);
        $receivedMessage = (new QueueReceiveMessageTransfer())->setQueueMessage($queueSendMessageTransfer);
        $receivedMessages = [$receivedMessage];

        $this->queueAdapterMock->expects(static::once())->method('createQueue');
        $this->queueEstablishmentHelperMock->expects(static::once())->method('createExchange');

        $this->queueAdapterMock->expects(static::exactly(2))
            ->method('receiveMessages')
            ->with($sourceQueueName, $chunkSize, $this->isType('array'))
            ->willReturnOnConsecutiveCalls($receivedMessages, []);

        $this->queueAdapterMock->expects(static::once())
            ->method('sendMessages')
            ->with($targetQueueName, [$queueSendMessageTransfer]);

        $this->queueAdapterMock->expects(static::once())
            ->method('acknowledge')
            ->with($receivedMessage);

        // Act
        $this->queueMessageMover->moveMessages($sourceQueueName, $targetQueueName, $chunkSize);
    }

    /**
     * @return void
     */
    public function testMoveMessagesWithEmptySourceQueue(): void
    {
        // Arrange
        $sourceQueueName = 'source-queue';
        $targetQueueName = 'target-queue';
        $chunkSize = 10;

        $this->queueAdapterMock->expects(static::once())->method('createQueue');
        $this->queueEstablishmentHelperMock->expects(static::once())->method('createExchange');

        $this->queueAdapterMock->expects(static::once())
            ->method('receiveMessages')
            ->with($sourceQueueName, $chunkSize, $this->isType('array'))
            ->willReturn([]);

        $this->queueAdapterMock->expects(static::never())->method('sendMessages');
        $this->queueAdapterMock->expects(static::never())->method('acknowledge');

        // Act
        $this->queueMessageMover->moveMessages($sourceQueueName, $targetQueueName, $chunkSize);
    }

    /**
     * @return void
     */
    public function testMoveMessagesInMultipleChunks(): void
    {
        // Arrange
        $sourceQueueName = 'source-queue';
        $targetQueueName = 'target-queue';
        $chunkSize = 1; // Process one message at a time

        $message1 = (new QueueReceiveMessageTransfer())->setQueueMessage((new QueueSendMessageTransfer())->setBody('1'));
        $message2 = (new QueueReceiveMessageTransfer())->setQueueMessage((new QueueSendMessageTransfer())->setBody('2'));

        $this->queueAdapterMock->expects(static::once())->method('createQueue');
        $this->queueEstablishmentHelperMock->expects(static::once())->method('createExchange');

        $this->queueAdapterMock->expects(static::exactly(3))
            ->method('receiveMessages')
            ->with($sourceQueueName, $chunkSize, $this->isType('array'))
            ->willReturnOnConsecutiveCalls([$message1], [$message2], []);

        $this->queueAdapterMock->expects(static::exactly(2))
            ->method('sendMessages');

        $this->queueAdapterMock->expects(static::exactly(2))
            ->method('acknowledge');

        // Act
        $this->queueMessageMover->moveMessages($sourceQueueName, $targetQueueName, $chunkSize);
    }
}

