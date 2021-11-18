<?php


namespace MingYuanYun\Push\Gateways;


use Apns\Client;
use Apns\Exception\ApnsException;
use Apns\Message;
use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\ApnsMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Exceptions\InvalidArgumentException;
use MingYuanYun\Push\Support\ArrayHelper;

class IosGateway extends Gateway
{
    const GATEWAY_NAME = 'ios';

    protected $maxTokens = 100;

    /**
     * @var Client $pusher
     */
    private $pusher = null;

    private $bundleId;

    public function getAuthToken()
    {
        return null;
    }

    public function setPusher(Client $pusher)
    {
        $this->pusher = $pusher;
    }

    private function checkPusher()
    {
        if (!isset($this->pusher)) {
            $isSandBox = $this->config->get('isSandBox');
            $certPath = $this->config->get('certPath');
            if (!file_exists($certPath)) {
                throw new InvalidArgumentException('无效的推送证书地址 > ' . $certPath);
            }
            $password = $this->config->get('password');

            $this->pusher = new Client(
                [$certPath, $password],
                $isSandBox
            );
        }
    }
    
    private function parseBundleId()
    {
        $cert = openssl_x509_parse(file_get_contents($this->pusher->getSslCert()[0]));
        if (!$cert) {
            throw new InvalidArgumentException('证书解析失败');
        }
        $this->bundleId = $cert['subject']['UID'];
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        $tokens = $this->formatTo($to);
        if (!$tokens) {
            throw new InvalidArgumentException('无有效的设备token');
        }
        if (!empty($options['push']) && $options['push'] instanceof Client) {
            $this->setPusher($options['push']);
        }
        $this->checkPusher();
        $this->parseBundleId();

        $payload = $this->createPayload($message);
        $messageEntity = new ApnsMessage();
        $messageEntity->setMessageEntity($payload);
        $messageEntity->setTopic($this->bundleId);

        $result = [];
        foreach ($tokens as $token) {
            $msg = clone $messageEntity;
            $msg->setDeviceIdentifier($token);
            try {
                $this->pusher->send($msg);
            } catch (ApnsException $e) {
                $result[$token] = $e->getMessage();
            }
        }
        if ($result) {
            throw new GatewayErrorException(json_encode($result));
        }
    }

    private function createPayload(AbstractMessage $message)
    {
        $messageData = [
            'aps' => [
                'alert' => [
                    'title' => $message->title,
                    'body' => $message->content,
                    'subtitle' => $message->subTitle,
                ],
                'sound' => 'default',
            ]
        ];
        if (! empty($message->badge)) {
            $messageData['aps']['badge'] = intval($message->badge);
        }
        if (! empty($message->extra)) {
            $messageData = ArrayHelper::merge($messageData, $message->extra);
        }
        $iosGatewayOption = is_array($message->gatewayOptions) ?
            ArrayHelper::getValue($message->gatewayOptions, 'ios') : [];
        $messageData = empty($iosGatewayOption) ?
            $messageData : ArrayHelper::merge($messageData, $iosGatewayOption);
        if (ArrayHelper::getValue($messageData, 'aps.mutable-content') == 1) {
            unset($messageData['aps']['sound']);
        }
        return $messageData;
    }

    public function __destruct()
    {
        $this->pusher && $this->getGatewayName() == static::GATEWAY_NAME && $this->pusher = null;
    }

    protected function formatTo($to)
    {
        if (!is_array($to)) {
            $to = [$to];
        }
        return array_filter($to, function ($item) {
            return ctype_xdigit($item) && strlen($item) == 64;
        });
    }
}