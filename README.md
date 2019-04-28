# MingYuanYun Push Library



## 平台支持

- [华为推送](https://developer.huawei.com/consumer/cn/service/hms/catalog/huaweipush_agent.html?page=hmssdk_huaweipush_api_reference_agent_s2)
- [小米推送](https://dev.mi.com/console/doc/detail?pId=1163)
- [魅族推送](https://github.com/MEIZUPUSH/PushAPI#api_standard_index)
- [Oppo推送](http://storepic.oppomobile.com/openplat/resource/201812/03/OPPO推送平台服务端API-V1.3.pdf)
- [Vivo推送](https://swsdl.vivo.com.cn/appstore/developer/uploadfile/20181123/20181123145345246.pdf)
- [iOS APNs推送](https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/APNSOverview.html#//apple_ref/doc/uid/TP40008194-CH8-SW1)
- [iOS APNs(base on token)推送](https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/establishing_a_token-based_connection_to_apns)


---

## 环境需求

- PHP >= 5.4
- guzzlehttp/guzzle >= 6.2
- yunchuang/php-jwt >= 1.0

## 安装

执行`composer require yunchuang/push`完成安装


---

## 使用

```php
use MingYuanYun\Push\Push;


$config = [
    'huawei' => [
        'appPkgName' => '', // 包名
        'clientId' => '',
        'clientSecret' => ''
    ],
    'meizu' => [
        'appPkgName' => '',
        'appId' => '',
        'appSecret' => ''
    ],
    'xiaomi' => [
        'appPkgName' => '',
        'appSecret' => ''
    ],
    'oppo' => [
        'appPkgName' => '',
        'appKey' => '',
        'masterSecret' => ''
    ],
    'vivo' => [
        'appPkgName' => '',
        'appId' => '',
        'appKey' => '',
        'appSecret' => ''
    ],
    'ios' => [
        'isSandBox' => true, // 是否沙盒环境（测试包）
        'certPath' => '', // pem格式推送证书本地绝对路径
        'password' => '123', // 推送证书密码
    ],
    'ios-token' => [
        'isSandBox' => true,
        'teamId' => 'D4GSYVE6CN', // 开发者帐号teamId
        'keyId' => '99BYW4U4SZ', // token认证keyId
        'secretFile' => 'xxx.p8', // token认证密钥文件本地绝对路径
        'bundleId' => 'com.mysoft.mdev' // 应用ID
    ]
];

$push = new Push($config);
$push->setPusher(通道名);
$push->pushNotice(设备token, 推送内容, 附加信息);

```

## 通道

目前支持以下通道：

- huawei 华为
- xiaomi 小米
- meizu 魅族
- oppo Oppo
- vivo Vivo
- ios 苹果(基于推送证书认证)
- ios-token 苹果(基于token认证)

## 设备token

通过推送插件获得，支持以数组形式传入多个。

鉴于各厂商对多推的支持不一，建议单次最多100个设备。


## 推送内容

由于各厂商对推送的支持不一，现抽象定义了以下公有属性：

|参数|类型|说明
|:---:|:---:|:---:|
| businessId | string | 业务ID |
| title | string | 标题，建议不超过10个汉字 |
| subTitle | string | 副标题，建议不超过10个汉字 |
| content | string | 内容，建议不超过20个汉字 |
| extra | array | 自定义数据，只支持一维 |
| callback | string | 送达回执地址，供推送厂商调用，最大128个字节，具体请查阅各厂商文档。*华为仅支持在应用管理中心配置；魅族需在管理后台注册回执地址，每次推送时也需指定回执地址；苹果仅ios-token通道支持回执* |
| callbackParam | string | 自定义回执参数，最大50个字节 |

示例

```php
$message = [
    'businessId' => uniqid(),
    'title' => 'This is title',
    'content' => 'This is content',
    'extra' => [
        'key1' => 'v1',
        'key2' => 2
    ]
];
```

## 附加信息

当前仅支持附加认证token

## 推送
```php

// 华为推送
$push->setPusher('huawei');
print $push->pushNotice(
    '0864113036098917300002377300CN01',
    $message,
    ['token' => 'CFx88jTVr6adjsh6eVOLvhtqnDlhLxb7CljykbXxu7vLsnexatUJZM1lqXHPzfnurD0gknQnIu7SRvWhAPx/zQ==']
);


// 魅族推送
$push->setPusher('meizu');
print $push->pushNotice(
    ['ULY6c596e6a7d5b714a475a60527c6b5f7f655a6d6370'],
    $message
);

// 小米推送
$push->setPusher('xiaomi');
print $push->pushNotice(
    [
        'hncl+mMTtpA8BQZ66k7Fgpwa+ezlSL8AN/g8HKzTfg64GcTeTjY1C9bdrUcs2vR+',
        '0VcFXBPNTLifGLIYK+GdDAiOFJQ+uWAzkfs7QYtfszBgqFV720C8zli7mce1mHj6'
    ],
    $message
);

// Oppo推送
$push->setPusher('oppo');
$tokenInfo = $push->getAuthToken();
$options = [
    'token' => $tokenInfo['token']
];
print $push->pushNotice(
    'CN_40557c137ac2b5c68cbb8ac52616fefd',
    $message,
    $options
);

// Vivo推送
$push->setPusher('vivo');
print $push->pushNotice(
    [
        '15513410784181118114099',
    ],
    $message
);

// 苹果基于证书推送
$push->setPusher('ios');
print $push->pushNotice(
    [
        '7438f5ba512cba4dcd1613e530a960cb862bd1c7ca70eae3cfe73137583c3c0d',
        '720772a4df1938b14d2b732ee62ce4e157577f8453d6021f81156aaeca7032ae',
    ],
    $message
);

// 苹果基于token推送
$push->setPusher('ios-token');
print $push->pushNotice(
    [
        '7438f5ba512cba4dcd1613e530a960cb862bd1c7ca70eae3cfe73137583c3c0d',
        '720772a4df1938b14d2b732ee62ce4e157577f8453d6021f81156aaeca7032ae',
    ],
    $message
);

```

## 认证

目前`华为`、`Oppo`、`Vivo`、`ios-token`推送前需要获取先获取认证token，且对获取频次均有限制，故统一提供了获取token方法`getAuthToken`，建议缓存认证token。

此方法返回格式如下：

```php
[
    'token' => 认证token,
    'expires' => 有效时间，单位为秒
];

```

其余通道将返回`null`。

缓存的认证token请以[附加信息](#附加信息)传入。


## 返回值

除苹果通道外，其余通道推送成功时均将返回推送任务ID。

调用过程中有可能抛出以下异常，请注意捕获。

- `MingYuanYun\Push\Exceptions\GatewayErrorException`
- `MingYuanYun\Push\Exceptions\InvalidArgumentException`

也可捕获上述异常的父类`MingYuanYun\Push\Exceptions\Exception`

## 自定义通道

本扩展支持自定义通道。

```php
use MingYuanYun\Push\Gateways\Gateway;

class MyGateway extends Gateway
{
    // ...
}

// 注册
$push->extend('custom', function ($config) {
    return new MyGateway($config);
}

// config中添加通道配置
$config['custom'] = [];

// 调用
$push->setPusher('custom');
print $push->pushNotice(
    '0864113036098917300002377300CN01',
    $message
);

```

## 各通道配置参照[$config](#使用)

---

## 各通道回执示例

- ios-token
```
# 成功
{
    "businessId": "5cc55570a9faf",
    "deviceToken": "7438f5ba512cba4dcd1613e530a960cb862bd1c7ca70eae3cfe73137583c3c0d",
    "taskId": "3FC1E078-93CA-33BA-EC52-8E8A70AA0EB1",
    "status": "success"
}

# 失败
{
    "businessId": "5cc550a6d05ad",
    "deviceToken": "7438f5ba512cba4dcd1613e530a960cb862bd1c7ca70eae3cfe73137583c3c0d",
    "reason": "403 Forbidden for ExpiredProviderToken",
    "status": "fail"
}
```
- huawei
```
{
    "statuses": [
        {
            "timestamp": 1552459811754,
            "token": "0864113036098917300002377300CN01",
            "appid": "100405075",
            "biTag": "",
            "status": 0,
            "requestId": "155245981150032647501"
        }
    ]
}
```
- meizu
```
{
    "cb": "{\"NS20190313171303747_0_11579902_1_3-1\":{\"param\":\"\",\"type\":1,\"targets\":[\"S5Q4b726f7b466c797c584d54000503555c427160754b\"]}}",
    "access_token": "c68b05216e54409d95573912fad9c0de"
}
```
- xiaomi
```
{
    "data": "{\"scm527795524699919378t\":{\"barStatus\":\"Enable\",\"type\":1,\"targets\":\"0VcFXBPNTLifGLIYK+GdDAiOFJQ+uWAzkfs7QYtfszBgqFV720C8zli7mce1mHj6\",\"timestamp\":1552470320982},\"scm52278552470202304Mq\":{\"barStatus\":\"Enable\",\"type\":1,\"targets\":\"0VcFXBPNTLifGLIYK+GdDAiOFJQ+uWAzkfs7QYtfszBgqFV720C8zli7mce1mHj6\",\"timestamp\":1552470321182}}"
}
```
- vivo
```
{
    "555758542050050048": {
        "param": null,
        "targets": "15513410784181118114099"
    }
}
```
- oppo
```
[
    {
        "registrationIds": "CN_768799ad17f2b564707db038dabb14b6",
        "messageId": "5c89f5d30980ff58c6ff9914",
        "taskId": "0000",
        "eventType": "push_arrive",
        "appId": "3000604"
    }
]
```