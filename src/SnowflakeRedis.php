<?php
namespace Hwphp;

use Hwphp\exception\SnowflakeException;

class SnowflakeRedis
{
    const FIRST_LENGTH = 1;

    const TIMESTAMP_LENGTH = 41;

    const DATACENTER_LENGTH = 5;

    const WORKER_LENGTH = 5;

    const SEQUENCE_LENGTH = 12;

    /**
     * 序列号最大值
     * @var int
     */
    protected $maxSequence = -1 ^ (-1 << self::SEQUENCE_LENGTH);

    /**
     * 数据中心最大值
     * @var int
     */
    protected $maxDatacenter = -1 ^ (-1 << self::DATACENTER_LENGTH);

    /**
     * 工作节点最大值
     * @var int
     */
    protected $maxWorkerId = -1 ^ (-1 << self::WORKER_LENGTH);

    /**
     * 起始时间
     * @var int
     */
    protected $startTimestamp = 1420041600000;

    /**
     * 数据中心Id
     * @var int
     */
    protected $datacenter;

    /**
     * 工作节点Id
     * @var int
     */
    protected $workerId;

    /**
     * 序列号
     * @var int
     */
    protected $sequenceNo;

    /**
     * 上次生成Id时间
     * @var int
     */
    protected $lastTimestamp;

    /**
     * Redis实例
     * @var $redis \Redis
     */
    protected $redis;

    /**
     * Redis前缀
     * @var string
     */
    protected $redisPrefix = 'SNOWFLAKE';

    /**
     * Redis缓存时间
     * @var int
     */
    protected $redisTtl = 30;

    /**
     * Snowflake constructor.
     * @param \Redis $redis
     * @param int $datacenter
     * @param int $workerId
     * @param array $options
     * @throws SnowflakeException
     */
    public function __construct(\Redis $redis, int $datacenter = 0, int $workerId = 0, array $options = [])
    {
        $this->redis = $redis;
        $this->setOptions($options);

        if ($datacenter > $this->maxDatacenter || $datacenter < 0) {
            throw new SnowflakeException(
                sprintf("Datacenter id can't be greater than %d or less than 0", $this->maxDatacenter)
            );
        }

        $this->datacenter = $datacenter;

        if ($workerId > $this->maxWorkerId || $workerId < 0) {
            throw new SnowflakeException(
                sprintf("Worker id can't be greater than %d or less than 0", $workerId)
            );
        }

        $this->workerId = $workerId;
    }

    /**
     * 生成Id
     * @return string
     * @throws SnowflakeException
     */
    public function id(): string
    {
        $this->lastTimestamp = $this->getLastTimestamp();
        $timestamp = $this->msTimestamp();

        if ($timestamp < $this->lastTimestamp) {
            throw new SnowflakeException(
                sprintf("Clock moved backwards. Refusing to generate id for %d milliseconds", $this->lastTimestamp - $timestamp)
            );
        }

        if ($timestamp === $this->lastTimestamp) {
            if (is_int($this->sequenceNo)) {
                $this->sequenceNo = ($this->sequenceNo + 1) & $this->maxSequence;

                if ($this->sequenceNo === 0) {
                    $timestamp = $this->waitMsTimestamp();
                }

            } else {
                $timestamp = $this->waitMsTimestamp();
                $this->sequenceNo = 0;
            }
        } else {
            $this->sequenceNo = 0;
        }

        $maxTimestamp = -1 ^ (-1 << self::TIMESTAMP_LENGTH);

        $timestampDiff = $timestamp - $this->startTimestamp;

        if ($timestampDiff < 0) {
            throw new SnowflakeException('The start time cannot be greater than the current time');
        }

        if ($timestampDiff > $maxTimestamp) {
            throw new SnowflakeException(sprintf('The current microtime - starttime is not allowed to exceed -1 ^ (-1 << %d), You can reset the start time to fix this', self::TIMESTAMP_LENGTH));
        }

        $this->setLastTimestamp($timestamp);

        $workerIdShift = self::SEQUENCE_LENGTH;
        $datacenterShift = self::WORKER_LENGTH + $workerIdShift;
        $timestampShift = self::DATACENTER_LENGTH + $datacenterShift;

        return (string)(($timestampDiff << $timestampShift)
            | ($this->datacenter << $datacenterShift)
            | ($this->workerId << $workerIdShift)
            | ($this->sequenceNo));
    }

    /**
     * 解析Id
     * @param $id
     * @return array
     */
    public function parseId($id): array
    {
        $id = decbin($id);

        return [
            'timestamp'  => bindec(substr($id, 0, -1 * (self::SEQUENCE_LENGTH + self::WORKER_LENGTH + self::DATACENTER_LENGTH))) + $this->startTimestamp,
            'datacenter' => bindec(substr($id, -1 * (self::SEQUENCE_LENGTH + self::WORKER_LENGTH + self::DATACENTER_LENGTH), self::DATACENTER_LENGTH)),
            'worker'     => bindec(substr($id, -1 * (self::SEQUENCE_LENGTH + self::WORKER_LENGTH), self::WORKER_LENGTH)),
            'sequence'   => bindec(substr($id, -1 * self::SEQUENCE_LENGTH)),
        ];
    }

    /**
     * 阻塞获取下一毫秒的时间戳
     * @return int
     */
    protected function waitMsTimestamp(): int
    {
        do {
            usleep(100);
            $timestamp = $this->msTimestamp();

        } while($timestamp <= $this->lastTimestamp);

        return $timestamp;
    }

    /**
     * 获取毫秒级的时间戳并写入到缓存
     * @return int
     */
    protected function msTimestamp(): int
    {
        return (int)round(microtime(true) * 1000);
    }

    /**
     * 获取上次生成Id时间
     * @return int
     */
    protected function getLastTimestamp(): int
    {
        $cacheKey = sprintf('%s:%s:%s', $this->redisPrefix, $this->datacenter, $this->workerId);
        $timestamp = $this->redis->get($cacheKey);

        if (!$timestamp) {
            return 0;
        }

        return (int)$timestamp;
    }

    /**
     * 存储最后生成Id时间
     * @param $timestamp
     * @return void
     */
    protected function setLastTimestamp($timestamp)
    {
        $this->lastTimestamp = $timestamp;
        $cacheKey = sprintf('%s:%s:%s', $this->redisPrefix, $this->datacenter, $this->workerId);
        $this->redis->set($cacheKey, (string)$this->lastTimestamp);
        $this->redis->expire($cacheKey, $this->redisTtl);
    }

    /**
     * 设置当前类的属性
     * @param $options
     * @return void
     */
    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
