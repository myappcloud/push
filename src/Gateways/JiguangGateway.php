<?php

namespace MingYuanYun\Push\Gateways;

use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class JiguangGateway extends Gateway
{
    use HasHttpRequest;

    const PUSH_URL = 'https://api.jpush.cn/v3/push';

    const OK_CODE = 0;

    const GATEWAY_NAME = 'jiguang';

    protected $maxTokens = 100;

    protected $headers = [
        'Content-Type' => 'application/json',
    ];


    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        if (!empty($options['token'])) {
            $token = $options['token'];
            unset($options['token']);
        } else {
            $tokenInfo = $this->getAuthToken();
            $token = $tokenInfo['token'];
        }
        $androidConfig = [
            'android' => [
                'alert' => $message->content,
                'title' => $message->title,
                'intent' => [
                    'url' => $this->generateIntent($this->config->get('appPkgName'), $message->extra)
                ]
            ]
        ];
        if ($message->badge) {
            $androidConfig['android']['badge_set_num'] = $message->badge;
        }
        $data = [
            'platform' => ['android'],
            'audience' => ['registration_id' => $this->formatTo($to)],
            'notification' => $androidConfig
        ];
        if(!empty($message->businessId)){
            /*
             * https://docs.jiguang.cn/jpush/server/push/rest_api_v3_push_advanced#%E8%8E%B7%E5%8F%96%E6%8E%A8%E9%80%81%E5%94%AF%E4%B8%80%E6%A0%87%E8%AF%86cid
             * cid 是用于防止 api 调用端重试造成服务端的重复推送而定义的一个推送参数。用户使用一个 cid 推送后，再次使用相同的 cid 进行推送，则会直接返回第一次成功推送的结果，不会再次进行推送。
             * cid 的有效期为 1 天。cid 的格式为：{appkey}-{uuid}
             */
            $data['cid'] = $message->businessId;
        }
        if (!empty($message->callback)) {

            $callback = [
                'url' => $message->callback
            ];
            if ($message->callbackParam) {
                $callback['params'] = $message->callbackParam;
            }
            $data['callback'] = $callback;
        }
        $this->setHeader('Authorization', 'Basic ' . $token);
        $data = $this->mergeGatewayOptions($data, $message->gatewayOptions);

        $result = $this->postJson(self::PUSH_URL, $data, $this->getHeaders());
        if (empty($result['msg_id'])) {
            throw new GatewayErrorException(sprintf(
                '极光推送失败 > [%s] %s',
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
        return $result['msg_id'];
    }

    public function getAuthToken()
    {
        $appKey = $this->config->get('appKey');
        $masterSecret = $this->config->get('masterSecret');
        if (empty($appKey) || empty($masterSecret)) {
            throw new GatewayErrorException(sprintf(
                '获取极光推送token失败 > [%s] %s',
                '-99',
                'appKey、masterSecret不能为空'
            ));
        }
        /*
         * 极光鉴权方式：https://docs.jiguang.cn/jpush/server/push/server_overview#%E9%89%B4%E6%9D%83%E6%96%B9%E5%BC%8F
         *  HTTP Header添加Authorization: Basic base64(appKey:masterSecret)
         */
        $token = base64_encode("$appKey:$masterSecret");
        return [
            'token' => $token,
            'expires' => strtotime('+1day') - time()
        ];
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