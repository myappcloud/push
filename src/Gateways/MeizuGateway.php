<?php


namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class MeizuGateway extends Gateway
{
    use HasHttpRequest;

    // https://github.com/MEIZUPUSH/PushAPI#api_standard_index

    const PUSH_URL = 'http://server-api-push.meizu.com/garcia/api/server/push/varnished/pushByPushId';

    const OK_CODE = '200';

    const GATEWAY_NAME = 'meizu';

    protected $headers = [
        'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    protected $maxTokens = 1000;


    public function getAuthToken()
    {
        return null;
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        $payload = [
            'noticeBarInfo' => [
                'title' => $message->title,
                'content' => $message->content,
            ],
            'clickTypeInfo' => [
                'clickType' => 2,
                'url' => $this->generateIntent($this->config->get('appPkgName'), $message->extra)
            ]
        ];
        if ($message->callback) {
            $payload['extra'] = [
                'callback' => $message->callback
            ];
            if ($message->callbackParam) {
                $payload['extra']['callback.param'] = $message->callbackParam;
            }
        }
        $payload = $this->mergeGatewayOptions($payload, $message->gatewayOptions);
        $data = [
            'appId' => $this->config->get('appId'),
            'pushIds' => $this->formatTo($to),
            'messageJson' => json_encode($payload),
        ];
        $data['sign'] = $this->generateSign($data);

        $result = $this->post(self::PUSH_URL, $data, $this->getHeaders());
        $this->assertFailure($result, '魅族推送失败');

        return $result['msgId'];
    }

    protected function generateSign($data)
    {
        ksort($data);
        $accessKeySecret = $this->config->get('appSecret');
        $stringToSign = $this->buildQuery($data, '');
        return md5($stringToSign . $accessKeySecret);
    }

    protected function formatTo($to)
    {
        if (is_array($to)) {
            $this->checkMaxToken($to);
            $to = implode(',', $to);
        }
        return $to;
    }

    protected function assertFailure($result, $message)
    {
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '%s > [%s] %s',
                $message,
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
    }
}