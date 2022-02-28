<?php
namespace Hwphp;

use Hwphp\exception\SnowflakeException;

class Snowflake
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
     * Snowflake constructor.
     * @param int $datacenter
     * @param int $workerId
     * @throws SnowflakeException
     */
    public function __construct(int $datacenter = 0, int $workerId = 0)
    {
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
        $timestamp = $this->msTimestamp();

        if ($timestamp < $this->lastTimestamp) {
            throw new SnowflakeException(
                sprintf("Clock moved backwards. Refusing to generate id for %d milliseconds", $this->lastTimestamp - $timestamp)
            );
        }

        if ($timestamp === $this->lastTimestamp) {
            $this->sequenceNo = ($this->sequenceNo + 1) & $this->maxSequence;

            if ($this->sequenceNo === 0) {
                $timestamp = $this->waitMsTimestamp();
            }
        } else {
            $this->sequenceNo = 0;
        }

        $this->lastTimestamp = $timestamp;

        $workerIdShift = self::SEQUENCE_LENGTH;
        $datacenterShift = self::WORKER_LENGTH + $workerIdShift;
        $timestampShift = self::DATACENTER_LENGTH + $datacenterShift;

        return (string)((($timestamp - $this->startTimestamp) << $timestampShift)
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
            'timestamp'  => bindec(substr($id, 0, -22)) + $this->startTimestamp,
            'datacenter' => bindec(substr($id, -22, 5)),
            'worker'     => bindec(substr($id, -17, 5)),
            'sequence'   => bindec(substr($id, -12)),
        ];
    }

    /**
     * 设置开始时间
     * @param $timestamp
     */
    public function setStartTimestamp($timestamp)
    {
        $this->startTimestamp = $timestamp;
    }

    /**
     * 获取毫秒级的时间戳
     * @return int
     */
    protected function msTimestamp(): int
    {
        return (int)round(microtime(true) * 1000);
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
}
