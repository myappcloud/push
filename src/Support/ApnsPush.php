<?php


namespace MingYuanYun\Push\Support;


class ApnsPush
{
    //错误信息
    private $error = array();
    //到服务器的socket连接句柄
    private $handle;
    //设备token
    private $deviceToken;
    //本地证书和密码
    private $localCert = '';
    private $passphrase = '';
    //是否沙盒模式
    private $isSandBox = false;

    public function getDeviceToken()
    {
        return $this->deviceToken;
    }

    public function getLocalCert()
    {
        return $this->localCert;
    }

    public function getPassphrase()
    {
        return $this->passphrase;
    }

    public function getIsSandBox()
    {
        return $this->isSandBox;
    }

    public function setDeviceToken($deviceToken)
    {
        $this->deviceToken = $deviceToken;
        return $this;
    }

    public function setLocalCert($localCert)
    {
        $this->localCert = $localCert;
        return $this;
    }

    public function setPassphrase($passphrase)
    {
        $this->passphrase = $passphrase;
        return $this;
    }

    public function setIsSandBox($isSandBox)
    {
        $this->isSandBox = $isSandBox;
        return $this;
    }

    public function connect()
    {
        $this->error = array();
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->localCert);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->passphrase);
        if ($this->isSandBox) {
            //这个是沙盒测试地址
            $this->handle = stream_socket_client(
                'ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        } else {
            //这个为正式地址
            $this->handle = stream_socket_client(
                'ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        }
        if (!$this->handle) {
            $this->error[] = "连接苹果APNs服务失败 > $err $errstr" . PHP_EOL;
        }
        return $this;
    }

    public function isSuccess()
    {
        return empty($this->error);
    }

    public function error()
    {
        return implode(PHP_EOL, $this->error);
    }

    private function createPayload($message, $badge, $sound, $extras = [])
    {
        $body['aps'] = array(
            'alert' => $message,
            'sound' => $sound,
            'badge' => $badge
        );
        unset($extras['aps']);
        $body = array_merge($body, $extras);

        $payload = json_encode($body);

        return $payload;
    }

    public function push($message, $badge = 1, $sound = 'default', $extras = [])
    {
        $this->error = array();
        $extras = is_array($extras) ? $extras : [];
        if (is_array($this->deviceToken)) {
            $tokens = $this->deviceToken;
            foreach ($tokens as $token) {
                $this->setDeviceToken($token)
                    ->_push($message, $badge, $sound, $extras);
            }
        } else {
            $this->_push($message, $badge, $sound, $extras);
        }
        return $this;
    }

    private function _push($message, $badge = 1, $sound = 'default', $extras = [])
    {
        // 创建消息
        $payload = $this->createPayload($message, $badge, $sound, $extras);
        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $this->deviceToken) . pack('n', strlen($payload)) . $payload;
        // Send it to the server
        $result = fwrite($this->handle, $msg, strlen($msg));
        if (!$result) {
            $this->error[] = sprintf('推送消息至设备[%s]失败') . $this->deviceToken . PHP_EOL;
        }
        return $this;
    }

    public function disconnect()
    {
        fclose($this->handle);
        return $this;
    }
}