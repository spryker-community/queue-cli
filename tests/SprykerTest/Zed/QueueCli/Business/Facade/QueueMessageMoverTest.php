<?php

namespace SprykerTest\Zed\QueueCli\Business\Model;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueMessageCliConfigurationTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use PhpAmqpLib\Channel\AMQPChannel;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Queue\Model\Adapter\AdapterInterface;
use Spryker\Client\RabbitMq\Model\Connection\Connection;
use Spryker\Client\RabbitMq\Model\Helper\QueueEstablishmentHelperInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface;
use SprykerCommunity\Zed\QueueCli\Business\Helper\InternalQueueBindingHelper;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageMover;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueMessageFilterInterface;
use SprykerCommunity\Zed\QueueCli\Business\Model\QueueSetupServiceInterface;

class QueueMessageMoverTest extends Unit
{
    protected MockObject|RabbitMqClientInterface $rabbitMqClientMock;
    protected MockObject|QueueEstablishmentHelperInterface $queueEstablishmentHelperMock;
    protected MockObject|AdapterInterface $queueAdapterMock;
    protected MockObject|Connection $connectionMock;
    protected MockObject $internalQueueBindingHelperMock;
    protected MockObject|QueueSetupServiceInterface $queueSetupServiceMock;
    protected MockObject|AMQPChannel $channelMock;
    protected MockObject|QueueMessageFilterInterface $filterMock;
    protected QueueMessageMover $queueMessageMover;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rabbitMqClientMock = $this->getMockBuilder(RabbitMqClientInterface::class)->getMock();
        $this->queueEstablishmentHelperMock = $this->getMockBuilder(QueueEstablishmentHelperInterface::class)->getMock();
        $this->queueAdapterMock = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $this->connectionMock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->channelMock = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
        $this->internalQueueBindingHelperMock = $this->getMockBuilder(InternalQueueBindingHelper::class)->disableOriginalConstructor()->getMock();
        $this->queueSetupServiceMock = $this->getMockBuilder(QueueSetupServiceInterface::class)->getMock();
        $this->filterMock = $this->getMockBuilder(QueueMessageFilterInterface::class)->getMock();

        $this->rabbitMqClientMock->method('createQueueAdapter')->willReturn($this->queueAdapterMock);
        $this->rabbitMqClientMock->method('getConnection')->willReturn($this->connectionMock);
        $this->connectionMock->method('getChannel')->willReturn($this->channelMock);

        $this->queueMessageMover = new QueueMessageMover(
            $this->rabbitMqClientMock,
            $this->queueEstablishmentHelperMock,
            $this->internalQueueBindingHelperMock,
            $this->queueSetupServiceMock,
            [$this->filterMock]
        );
    }

    public function testMoveMessagesFiltersAndAcknowledgesCorrectly(): void
    {
        // Arrange
        $sourceQueue = 'source-queue';
        $targetQueue = 'target-queue';
        $chunkSize = 10;

        $sendMessageTransfer = (new QueueSendMessageTransfer())->setBody('test-message');
        $receiveMessageTransfer = (new QueueReceiveMessageTransfer())->setQueueMessage($sendMessageTransfer);

        $configurationTransfer = (new QueueMessageCliConfigurationTransfer())
            ->setSourceQueue($sourceQueue)
            ->setTargetQueue($targetQueue)
            ->setChunkSize($chunkSize)
            ->setLimit(null)
            ->setKeep(false)
            ->setFilter('some-filter');

        $this->queueSetupServiceMock->expects(static::once())
            ->method('setupTargetQueue')
            ->with($targetQueue);

        $this->filterMock->expects(static::once())
            ->method('matches')
            ->with($receiveMessageTransfer, 'some-filter')
            ->willReturn(true);

        $this->queueAdapterMock->expects(static::exactly(1))
            ->method('receiveMessages')
            ->willReturnOnConsecutiveCalls([$receiveMessageTransfer], []);

        $this->queueAdapterMock->expects(static::once())
            ->method('sendMessages')
            ->with($targetQueue, [$sendMessageTransfer]);

        $this->queueAdapterMock->expects(static::once())
            ->method('acknowledge')
            ->with($receiveMessageTransfer);

        // Act
        $processedCount = $this->queueMessageMover->moveMessages($configurationTransfer);

        // Assert
        $this->assertEquals(1, $processedCount);
    }

    public function testMoveMessagesSkipsWhenFilterFails(): void
    {
        // Arrange
        $sourceQueue = 'source-queue';
        $targetQueue = 'target-queue';
        $chunkSize = 10;

        $sendMessageTransfer = (new QueueSendMessageTransfer())->setBody('ignored');
        $receiveMessageTransfer = (new QueueReceiveMessageTransfer())->setQueueMessage($sendMessageTransfer);

        $configurationTransfer = (new QueueMessageCliConfigurationTransfer())
            ->setSourceQueue($sourceQueue)
            ->setTargetQueue($targetQueue)
            ->setChunkSize($chunkSize)
            ->setLimit(null)
            ->setKeep(false)
            ->setFilter('some-filter');

        $this->queueSetupServiceMock->method('setupTargetQueue');

        $this->filterMock->expects(static::once())
            ->method('matches')
            ->with($receiveMessageTransfer, 'some-filter')
            ->willReturn(false);

        $this->queueAdapterMock->expects(static::once())
            ->method('receiveMessages')
            ->willReturn([$receiveMessageTransfer]);

        $this->queueAdapterMock->expects(static::never())->method('sendMessages');
        $this->queueAdapterMock->expects(static::never())->method('acknowledge');

        // Act
        $processedCount = $this->queueMessageMover->moveMessages($configurationTransfer);

        // Assert
        $this->assertEquals(0, $processedCount);
    }
}
