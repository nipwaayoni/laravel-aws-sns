<?php


namespace MiamiOH\SnsHandler;

class SnsTopicMapper
{
    private $map = [];

    /**
     * SnsTopicMapper constructor.
     * @param array $map
     * @throws SnsException
     * @throws \ReflectionException
     */
    public function __construct(array $map = [])
    {
        foreach ($map as $arn => $class) {
            $this->map($arn, $class);
        }
    }

    /**
     * @param string $topicArn
     * @param string $class
     * @throws SnsException
     * @throws \ReflectionException
     */
    public function map(string $topicArn, string $class): void
    {
        $reflection = new \ReflectionClass($class);
        $interfaces = $reflection->getInterfaceNames();

        if (!in_array(SnsMessageHandler::class, $interfaces)) {
            throw new SnsException('Mapper targets must be SnsMessageHandler classes');
        }

        $this->map[$topicArn] = $class;
    }

    /**
     * @param string $topicArn
     * @return bool
     */
    public function hasMapForTopic(string $topicArn): bool
    {
        return array_key_exists($topicArn, $this->map);
    }

    /**
     * @param string $topicArn
     * @return SnsMessageHandler
     * @throws SnsUnknownTopicArnException
     */
    public function getHandlerForTopic(string $topicArn): SnsMessageHandler
    {
        if (!$this->hasMapForTopic($topicArn)) {
            throw new SnsUnknownTopicArnException($topicArn);
        }

        $class = $this->map[$topicArn];

        return new $class();
    }
}
