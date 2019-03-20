<?php


namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\Contracts\MessageInterface;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Exceptions\InvalidArgumentException;
use MingYuanYun\Push\Support\ApnsPush;

class IosGateway extends Gateway
{
    protected $maxTokens = 100;

    public function getAuthToken()
    {
        return null;
    }

    public function pushNotice($to, MessageInterface $message, array $options = [])
    {
        $to = $this->formatTo($to);
        if (!$to) {
            throw new InvalidArgumentException('无有效的设备token');
        }
        $push = new ApnsPush();
        $isSandBox = $this->config->get('isSandBox');
        $certPath = $this->config->get('certPath');
        if (!file_exists($certPath)) {
            throw new InvalidArgumentException('无效的推送证书地址 > ' . $certPath);
        }

        $push->setIsSandBox($isSandBox)
            ->setLocalCert($certPath);
        if ($password = $this->config->get('password')) {
            $push->setPassphrase($password);
        }
        $push->connect();

        if ($push->isSuccess()) {
            // http://docs.getui.com/getui/server/rest/template/
            // https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/PayloadKeyReference.html
            $messageData = [
                'title' => $message->title,
                'body' => $message->content,
                'apns-collapse-id' => $message->businessId
            ];
            $push->setDeviceToken($to)
                ->push($messageData, $message->badge, 'default', $message->extra);
        }
        $error = $push->error();
        $push->disconnect();
        if ($error) {
            throw new GatewayErrorException($error);
        }
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