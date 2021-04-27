<?php

namespace Nipwaayoni\Tests\SnsHandler\Unit;

use Nipwaayoni\SnsHandler\SnsException;
use Nipwaayoni\SnsHandler\SnsTopicMapper;
use Nipwaayoni\SnsHandler\SnsUnknownTopicArnException;
use Nipwaayoni\Tests\SnsHandler\TestCase;

class SnsTopicMapperTest extends TestCase
{
    /** @var SnsTopicMapper  */
    private $mapper;

    public function setUp(): void
    {
        $this->mapper = new SnsTopicMapper();
        $this->mapper->map(
            'arn:aws:sns:us-west-2:123456789012:MyTopic',
            SnsMessageHandlerStub::class
        );
    }

    public function testCanBeInitializedWithMapArray(): void
    {
        $map = [
            'arn:aws:sns:us-west-2:123456789012:MyTopic' => SnsMessageHandlerStub::class,
        ];

        $mapper = new SnsTopicMapper($map);

        $this->assertTrue($mapper->hasMapForTopic('arn:aws:sns:us-west-2:123456789012:MyTopic'));
    }

    public function testRequiresMapTargetToBeSnsMessageHandler(): void
    {
        $this->expectException(SnsException::class);
        $this->expectExceptionMessage('Mapper targets must be SnsMessageHandler classes');

        $this->mapper->map('abc123', \stdClass::class);
    }

    /**
     * @dataProvider topicArnMapChecks
     */
    public function testAssertsHasMapForTopic(string $topicArn, bool $expected): void
    {
        $this->assertEquals($expected, $this->mapper->hasMapForTopic($topicArn));
    }

    public function topicArnMapChecks(): array
    {
        return [
            'mapped topic' => ['arn:aws:sns:us-west-2:123456789012:MyTopic', true],
            'unmapped topic' => ['arn:aws:sns:us-west-2:123456789012:Unknown', false],
        ];
    }

    public function testReturnsNewHandlerForMappedTopicArn(): void
    {
        $handler = $this->mapper->getHandlerForTopic('arn:aws:sns:us-west-2:123456789012:MyTopic');

        $this->assertInstanceOf(SnsMessageHandlerStub::class, $handler);
    }

    public function testThrowsExceptionGettingHandlerForUnknownArn(): void
    {
        $this->expectException(SnsUnknownTopicArnException::class);
        $this->expectExceptionMessage('No handler registered for TopicArn arn:aws:sns:us-west-2:123456789012:Unknown');

        $this->mapper->getHandlerForTopic('arn:aws:sns:us-west-2:123456789012:Unknown');
    }
}
