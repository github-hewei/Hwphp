<?php //CODE BY HW
namespace Hwphp\exception;

/**
 * cURL异常处理类
 */
class CurlException extends \Exception {

    /**
     * cURL错误编号
     * @var int
     */
    public $curl_errorno;

    /**
     * cURLException constructor.
     * @param string $message 异常信息
     * @param int $curl_errorno cURL错误编号
     * @param int $code 异常错误编号
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $curl_errorno = 0, $code = 0, \Exception $previous = null) {
        $this->curl_errorno = $curl_errorno;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取cURL错误信息
     * @return NULL|string
     */
    public function curl_strerror() {
        return function_exists('curl_strerror') ? curl_strerror($this->curl_errorno) : '';
    }

}
