<?php

namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class HuaweiV2Gateway extends Gateway
{
    use HasHttpRequest;

    // https://developer.huawei.com/consumer/cn/doc/development/HMS-References/push-sendapi

    const AUTH_URL = 'https://oauth-login.cloud.huawei.com/oauth2/v2/token';

    // https://push-api.cloud.huawei.com/v1/[appid]/messages:send
    const PUSH_URL = 'https://push-api.cloud.huawei.com/v1/%s/messages:send';

    const OK_CODE = '80000000';

    const GATEWAY_NAME = 'huawei-v2';

    protected $maxTokens = 1000;

    protected $headers = [
        'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        if (! empty($options['token'])) {
            $token = $options['token'];
            unset($options['token']);
        } else {
            $tokenInfo = $this->getAuthToken();
            $token = $tokenInfo['token'];
        }

        $androidConfig = [
            'collapse_key' => -1,
            'bi_tag' => $message->businessId ?: '',
            'notification' => [
                'title' => $message->title,
                'body' => $message->content ,
                'tag' => $message->notifyId ?: null,
                'notify_id' => $message->notifyId ?: -1,
                'click_action' => [
                    'type' => 1,
                    'intent' => $this->generateIntent($this->config->get('appPkgName'), $message->extra),
                ]
            ]
        ];
        if ($message->badge) {
            if (preg_match('/^\d+$/', $message->badge)) {
                $androidConfig['notification']['badge'] = [
                    'set_num' => intval($message->badge),
                    'class' => 'com.mysoft.core.activity.LauncherActivity'
                ];
            } else {
                $androidConfig['notification']['badge'] = [
                    'add_num' => intval($message->badge),
                    'class' => 'com.mysoft.core.activity.LauncherActivity'
                ];
            }
        }
        $androidConfig = $this->mergeGatewayOptions($androidConfig, $message->gatewayOptions);
        $data = [
            'message' => [
                'token' => $this->formatTo($to),
                'android' => $androidConfig,
            ],
        ];

        $this->setHeader('Authorization', 'Bearer ' . $token);

        $result = $this->postJson($this->buildPushUrl(), $data, $this->getHeaders());
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '华为推送失败 > [%s] %s %s',
                isset($result['code']) ? $result['code'] : '-99',
                isset($result['error']) ? $result['error'] : '',
                isset($result['msg']) ? $result['msg'] : '未知异常'
            ));
        }
        return $result['requestId'];
    }

    public function getAuthToken()
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config->get('clientId'),
            'client_secret' => $this->config->get('clientSecret')
        ];
        $result = $this->post(self::AUTH_URL, $data, $this->getHeaders());

        if (!isset($result['access_token'])) {
            throw new GatewayErrorException(sprintf(
                '获取华为推送token失败 > [%s] %s',
                isset($result['error']) ? $result['error'] : '-99',
                isset($result['error_description']) ? $result['error_description'] : '未知异常'
            ));
        }

        return [
            'token' => $result['access_token'],
            'expires' => $result['expires_in']
        ];
    }

    protected function getTimestamp()
    {
        return strval(time());
    }

    protected function buildPushUrl()
    {
        return sprintf(self::PUSH_URL, $this->config->get('clientId'));
    }

    protected function formatTo($to)
    {
        if (!is_array($to)) {
            $to = [$to];
        } else {
            $this->checkMaxToken($to);
        }
        return $to;
    }
}