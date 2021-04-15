# Hwphp
> 安装方法
```sh
composer require hwphp/hwphp
```

> Curl用法示例一
```php
<?php //CODE BY HW
require_once 'vendor/autoload.php';

use Hwphp\Curl;
use Hwphp\curl\Exception as CurlException;

try{
    $curl = new Curl('http://www.qq.com', 'GET', ['query' => 'value'], [
        CURLOPT_HTTPHEADER => [
            'X-Requested-With: XMLHttpRequest'
        ],
    ]);
    $content = $curl->exec();
    $handleInfo = $curl->getInfo();
    var_dump($content);
    var_dump($handleInfo);
    exit;

}catch(CurlException $e) {
    if($e->getCode() === 500) {
        var_dump('cURL ERROR: ' . $e->curl_strerror());
        exit;
    }
    throw $e;
}catch(\Exception $e) {
    throw $e;
}

```

> Curl用法示例二
```php
<?php //CODE BY HW
require_once 'vendor/autoload.php';

use Hwphp\Curl;
use Hwphp\curl\Exception as CurlException;

try{
    $curl = new Curl();
    $curl->setUrl('https://www.qq.comxx');
    $curl->setCookieFile();
    $curl->setUa();
    $curl->setData(['query' => 'value']);
    $curl->setMethod('GET');
    $curl->setNoSSL();
    $curl->setXhr();
    $curl->setFile('d:/temp/NotFound.file');
    $content = $curl->exec();
    $handleInfo = $curl->getInfo();
    var_dump($content);
    var_dump($handleInfo);
    exit;

}catch(CurlException $e) {
    if($e->getCode() === 500) {
        var_dump('cURL ERROR: ' . $e->curl_strerror());
        exit;
    }
    throw $e;
}catch(\Exception $e) {
    throw $e;
}

```