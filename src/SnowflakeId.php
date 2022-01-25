<?php // CODE BY HW
namespace Hwphp;

use Hwphp\exception\SnowflakeIdException;

/**
 * 雪花算法生成Id
 * @date 2022-01-25
 * @author hw
 */
class SnowflakeId
{
    /**
     * 初始时间，毫秒级时间戳，默认初始为2015-01-01 00:00:00
     * @var int
     */
    protected $initialTimestamp = 1420041600000;

    /**
     * 序列号所占的位数（12位支持4096个序列号）
     * @var int
     */
    protected $sequenceBits = 12;

    /**
     * 序列号掩码
     * @var int
     */
    protected $sequenceMask = -1 ^ (-1 << 12);

    /**
     * 机器号位数（10位支持1024个设备）
     * @var int
     */
    protected $workerBits = 10;

    /**
     * 机器码左移位数（序列号12位）
     * @var int
     */
    protected $workerShift = 12;

    /**
     * 机器码最大值
     * @var int
     */
    protected $workerMax = -1 ^ (-1 << 10);

    /**
     * 毫秒时间戳位数（41位支持69年）
     * @var int
     */
    protected $timestampBits = 41;

    /**
     * 时间戳左移位数（序列号12位 + 设备号10位）
     * @var int
     */
    protected $timestampShift = 22;

    /**
     * 设备号
     * @var int|mixed
     */
    protected $workerId;

    /**
     * 上次生成id的时间戳
     * @var int
     */
    protected $lastTimestamp = 0;

    /**
     * 同一毫秒内的id序列号
     * @var int
     */
    protected $sequenceNo = 0;

    /**
     * SnowflakeId constructor.
     * @param int $workerId
     * @throws SnowflakeIdException
     */
    public function __construct($workerId = 1)
    {
        if ($workerId > $this->workerMax || $workerId < 0) {
            throw new SnowflakeIdException(
                sprintf("worker Id can't be greater than %d or less than 0", $this->workerMax)
            );
        }

        $this->workerId = $workerId;
    }

    /**
     * 生成id
     * @return int
     * @throws SnowflakeIdException
     */
    public function generate()
    {
        $timestamp = $this->msTimestamp();

        if ($timestamp < $this->lastTimestamp) {
            throw new SnowflakeIdException(
                sprintf("Clock moved backwards. Refusing to generate id for %d milliseconds", $this->lastTimestamp - $timestamp)
            );
        }

        if ($timestamp === $this->lastTimestamp) {
            $this->sequenceNo = ($this->sequenceNo + 1) & $this->sequenceMask;

            if ($this->sequenceNo === 0) {
                $timestamp = $this->waitMsTimestamp();
            }

        } else {
            $this->sequenceNo = 0;
        }

        $this->lastTimestamp = $timestamp;

        return (($timestamp - $this->initialTimestamp) << $this->timestampShift)
            | ($this->workerId << $this->workerShift)
            | $this->sequenceNo;
    }

    /**
     * 解析Id的 [时间戳,机器码,序列号]
     * @param $id
     * @return array
     */
    public function parse($id)
    {
        $sequenceNo = $id & (-1 ^ (-1 << $this->sequenceBits));

        $workerId = ($id & ((-1 << $this->workerShift) ^ (-1 << $this->timestampShift))) >> $this->workerShift;

        $timestamp = ($id & ((-1 << $this->timestampShift) ^ (-1 << ($this->timestampShift + $this->timestampBits)))) >> $this->timestampShift;

        $timestamp = $timestamp + $this->initialTimestamp;

        return [$timestamp, $workerId, $sequenceNo];
    }

    /**
     * 获取毫秒级的时间戳
     * @return int
     */
    protected function msTimestamp()
    {
        return (int)round(microtime(true) * 1000);
    }

    /**
     * 阻塞获取下一毫秒的时间戳
     * @return int
     */
    protected function waitMsTimestamp()
    {
        do {
            usleep(100);
            $timestamp = $this->msTimestamp();

        } while($timestamp <= $this->lastTimestamp);

        return $timestamp;
    }
}
