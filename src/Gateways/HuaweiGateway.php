<?php

namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class HuaweiGateway extends Gateway
{
    use HasHttpRequest;

    // https://developer.huawei.com/consumer/cn/service/hms/catalog/huaweipush_agent.html?page=hmssdk_huaweipush_api_reference_agent_s2

    const AUTH_URL = 'https://login.cloud.huawei.com/oauth2/v2/token';

    const PUSH_URL = 'https://api.push.hicloud.com/pushsend.do';

    const VER = '1';

    const NSP_SVC = 'openpush.message.api.send';

    const OK_CODE = '80000000';

    const GATEWAY_NAME = 'huawei';

    protected $maxTokens = 100;

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
        $payload = [
            'hps' => [
                'msg' => [
                    'type' => 3,
                    'body' => [
                        'content' => $message->content,
                        'title' => $message->title,
                    ],
                    'action' => [
                        'type' => 1,
                        'param' => [
                            'appPkgName' => $this->config->get('appPkgName'),
                            'intent' => $this->generateIntent($this->config->get('appPkgName'), $message->extra)
                        ]
                    ]
                ],
                'ext' => [
                    'biTag' => $message->businessId ?: '',
                ]
            ]
        ];
        if ($message->badge) {
            $payload['hps']['ext']['badgeAddNum'] = strval($message->badge);
            $payload['hps']['ext']['badgeClass'] = 'com.mysoft.core.activity.LauncherActivity';
        }
        $payload = $this->mergeGatewayOptions($payload, $message->gatewayOptions);
        $data = [
            'access_token' => $token,
            'nsp_ts' => $this->getTimestamp(),
            'nsp_svc' => self::NSP_SVC,
            'device_token_list' => $this->formatTo($to),
            'payload' => json_encode($payload)
        ];
        $result = $this->post($this->buildPushUrl(), $data, $this->getHeaders());
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '华为推送失败 > [%s] %s',
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
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
        $params = [
            'nsp_ctx' => json_encode([
                'ver' => self::VER,
                'appId' => $this->config->get('clientId')
            ]),
        ];
        $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        return sprintf('%s?%s', self::PUSH_URL, $queryStr);
    }

    protected function formatTo($to)
    {
        if (!is_array($to)) {
            $to = [$to];
        } else {
            $this->checkMaxToken($to);
        }
        return json_encode($to);
    }
}