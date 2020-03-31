<?php


namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Exceptions\InvalidArgumentException;
use MingYuanYun\Push\Support\ApnsPush;

class IosGateway extends Gateway
{
    const GATEWAY_NAME = 'ios';

    protected $maxTokens = 100;

    /**
     * @var ApnsPush $pusher
     */
    protected $pusher = null;

    public function getAuthToken()
    {
        return null;
    }

    public function setPusher(ApnsPush $pusher)
    {
        $this->pusher = $pusher;
    }

    private function checkPusher()
    {
        if (! isset($this->pusher)) {
            $this->pusher = new ApnsPush();
            $isSandBox = $this->config->get('isSandBox');
            $certPath = $this->config->get('certPath');
            if (!file_exists($certPath)) {
                throw new InvalidArgumentException('无效的推送证书地址 > ' . $certPath);
            }

            $this->pusher->setIsSandBox($isSandBox)
                ->setLocalCert($certPath);
            if ($password = $this->config->get('password')) {
                $this->pusher->setPassphrase($password);
            }
        }
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        $to = $this->formatTo($to);
        if (!$to) {
            throw new InvalidArgumentException('无有效的设备token');
        }
        if (! empty($options['push']) && $options['push'] instanceof ApnsPush) {
            $this->setPusher($options['push']);
        }

        $this->checkPusher();
        $this->pusher->connect();

        if ($this->pusher->isSuccess()) {
            // http://docs.getui.com/getui/server/rest/template/
            // https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/PayloadKeyReference.html
            $messageData = [
                'title' => $message->title,
                'body' => $message->content,
                'apns-collapse-id' => $message->businessId
            ];
            $this->pusher->setDeviceToken($to)
                ->push($messageData, intval($message->badge), 'default', $message->extra);
            return;
        }
        $error = $this->pusher->error();
        if ($error) {
            throw new GatewayErrorException($error);
        }
    }

    public function __destruct()
    {
        $this->pusher && $this->pusher->disconnect();
    }

    protected function formatTo($to)
    {
        if (!is_array($to)) {
            $to = [$to];
        }
        return array_filter($to, function ($item) {
            return ctype_xdigit($item);
        });
    }
}