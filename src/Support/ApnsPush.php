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

    /**
     * 获取设备token
     * @return type
     */
    function getDeviceToken() {
        return $this->deviceToken;
    }

    /**
     * 证书路径
     * @return type
     */
    function getLocalCert() {
        return $this->localCert;
    }

    /**
     * 证书密码
     * @return type
     */
    function getPassphrase() {
        return $this->passphrase;
    }

    /**
     * 是否是沙盒子模式
     * @return type
     */
    function getIsSandBox() {
        return $this->isSandBox;
    }

    /**
     * 设置设备token
     * @param type $deviceToken
     * @return self
     */
    function setDeviceToken($deviceToken) {
        $this->deviceToken = $deviceToken;
        return $this;
    }

    /**
     * 设置证书路径
     * @param type $localCert
     * @return self
     */
    function setLocalCert($localCert) {
        $this->localCert = $localCert;
        return $this;
    }

    /**
     * 设置证书密码
     * @param type $passphrase
     * @return self
     */
    function setPassphrase($passphrase) {
        $this->passphrase = $passphrase;
        return $this;
    }

    /**
     * 设置是否是沙盒模式
     * @param type $isSandBox
     * @return self
     */
    function setIsSandBox($isSandBox) {
        $this->isSandBox = $isSandBox;
        return $this;
    }

    /*
     * 连接apns服务器
     */

    public function connect() {
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

    /**
     * 操作是否成功
     * @return boolean
     */
    public function isSuccess() {
        return empty($this->error);
    }

    /**
     * 错误消息
     * @return string
     */
    public function error() {
        return implode(PHP_EOL, $this->error);
    }

    /*
      功能：生成发送内容并且转化为json格式
     */

    private function createPayload($message, $badge, $sound, $extras = []) {
        $body['aps'] = array(
            'alert' => $message,
            'sound' => $sound,
            'badge' => $badge
        );
        unset($extras['aps']);
        $body = array_merge($body, $extras);

        // Encode the payload as JSON
        $payload = json_encode($body);

        return $payload;
    }

    /**
     * 推送消息
     * @param type $message
     * @param type $badge
     * @param type $sound
     * @return self
     */
    public function push($message, $badge = 1, $sound = 'default', $extras = []) {
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

    private function _push($message, $badge = 1, $sound = 'default', $extras = []) {
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

    /**
     * 断开到服务器的连接
     * @return self
     */
    public function disconnect() {
        fclose($this->handle);
        return $this;
    }
}