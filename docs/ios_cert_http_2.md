# iOS证书推送环境检测

## 查看是否支持基于HTTP/2的cURL
- 通过PHP方法
```
<?php

defined('CURL_VERSION_HTTP2') || define('CURL_VERSION_HTTP2', 65536);
$version = curl_version();
if (($version['features'] & CURL_VERSION_HTTP2) !== 0) {
        echo 'HTTP/2 supported'.PHP_EOL;
} else {
        echo 'HTTP/2 not supported'.PHP_EOL;

```
- 通过 `curl --version`，要求版本大于**7.43.0**

## 安装支持HTTP/2的curl
- Ubuntu
```
#!/bin/bash

# Update version to latest, found here: https://curl.se/download/
VERSION=7.76.1

cd ~
sudo apt-get update -y
sudo apt-get install -y build-essential nghttp2 libnghttp2-dev libssl-dev wget
wget https://curl.haxx.se/download/curl-${VERSION}.tar.gz
tar -xzvf curl-${VERSION}.tar.gz && rm -f curl-${VERSION}.tar.gz && cd curl-${VERSION}
./configure --prefix=/usr/local --with-ssl --with-nghttp2
make -j4
sudo make install
sudo ldconfig
cd ~ && rm -rf curl-${VERSION}
```
- MacOS X
```
brew install curl --with-nghttp2 --with-openssl
brew link curl --force
brew reinstall php56 --with-homebrew-curl

``` 
