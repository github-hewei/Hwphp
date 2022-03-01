# Hwphp

## 安装方法
> 安装方法
```sh
composer require hwphp/hwphp
```
## SnowflakeId
> 生成雪花Id示例
```php
<?php
require_once 'vendor/autoload.php';

use Hwphp\Snowflake;
use Hwphp\exception\SnowflakeException;
try {
    $snowflake = new Snowflake(1, 1);
    $snowflake->setStartTimestamp(strtotime('2022-01-01 00:00:00') * 1000);

    for ($i = 0; $i < 10; $i++) {
        $id = $snowflake->id();
        $parse = $snowflake->parseId($id);
        var_dump($id, $parse, date('Y-m-d H:i:s', ceil($parse['timestamp'] / 1000)));
    }

} catch(SnowflakeException $e) {
    echo $e;
}

```

## Tree
> Tree 用法示例

```php
<?php // CODE BY HW 
require_once 'vendor/autoload.php';

use Hwphp\Tree;
$rows = [
    [
        'id' => 1,
        'name' => 'aa',
        'pid' => 0,
    ],
    [
        'id' => 2,
        'name' => 'bb',
        'pid' => 1,
    ],
    [
        'id' => 3,
        'name' => 'cc',
        'pid' => 2,
    ],
    [
        'id' => 4,
        'name' => 'dd',
        'pid' => 0,
    ]
];

$tree = Tree::get($rows, ['appendLevel' => true, 'appendIdx' => true], function($item) {
    if ($item['_idx'] == 0) { // 当前数组第一个元素
        
    }
    if ($item['_idx'] == $item['_idxMax']) {// 当前数组最后一个元素
        
    }
    // $item['_level']; // 当前元素级别
    
    $item['region_name'] = $item['name'] .'.updated';
    
    // 最后必须将修改后的元素返回
    return $item;
});
```

## Curl
> Curl用法示例一

```php
<?php //CODE BY HW
require_once 'vendor/autoload.php';

use Hwphp\Curl;
use Hwphp\exception\CurlException;

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
use Hwphp\exception\CurlException;

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
