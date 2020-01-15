# ios-token推送说明

---

## 1、苹果基于token的推送所使用的通信协议为HTTP/2
> [https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns](https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns)

## 2、验证当前PHP是否支持HTTP2
```shell
$ php --ri curl
```
![php_curl_info.png](php_curl_info.png)


## 3、不支持时如何处理
通过上图，确认是否满足以下条件

- cURL Information >= 7.54.0
- SSL Version >= OpenSSL/1.0.2s

如果不满足，则需升级`curl`和/或`openssl`

## 4、升级后如何验证是否满足上步要求
```shell
$ curl -I https://nghttp2.org
```
![curl_http2_test.png](curl_http2_test.png)

如果返回头中标识的协议为`HTTP/2`，则表示达到要求，此时也可通过`php --ri curl`验证是否支持`HTTP/2`。


---

#### 可用的apt-get源
```
deb http://mirrors.163.com/debian/ stretch main non-free contrib
deb http://mirrors.163.com/debian/ stretch-updates main non-free contrib
deb http://mirrors.163.com/debian/ stretch-backports main non-free contrib
deb-src http://mirrors.163.com/debian/ stretch main non-free contrib
deb-src http://mirrors.163.com/debian/ stretch-updates main non-free contrib
deb-src http://mirrors.163.com/debian/ stretch-backports main non-free contrib
deb http://mirrors.163.com/debian-security/ stretch/updates main non-free contrib
deb-src http://mirrors.163.com/debian-security/ stretch/updates main non-free contrib
```