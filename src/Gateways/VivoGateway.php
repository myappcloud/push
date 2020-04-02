<?php


namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class VivoGateway extends Gateway
{
    use HasHttpRequest;

    // https://swsdl.vivo.com.cn/appstore/developer/uploadfile/20191210/we5XL6/PUSH-UPS-API接口文档%20-%202.7.0版.pdf

    const BASE_URL = 'https://api-push.vivo.com.cn';

    const AUTH_METHOD = 'message/auth';

    const SINGLE_PUSH_METHOD = 'message/send';

    const SAVE_MESSAGE_METHOD = 'message/saveListPayload';

    const MULTI_PUSH_METHOD = 'message/pushToList';

    const OK_CODE = 0;

    const GATEWAY_NAME = 'vivo';

    protected $maxTokens = 1000;

    protected $headers = [
        'Content-Type' => 'application/json'
    ];

    public function getAuthToken()
    {
        $data = [
            'appId' => $this->config->get('appId'),
            'appKey' => $this->config->get('appKey'),
            'timestamp' => $this->getTimestamp()
        ];
        $data['sign'] = $this->generateSign($data);

        $result = $this->postJson(
            sprintf('%s/%s', self::BASE_URL, self::AUTH_METHOD),
            $data,
            $this->getHeaders()
        );

        $this->assertFailure($result, '获取Vivo推送token失败');

        return [
            'token' => $result['authToken'],
            'expires' => strtotime('+1day') - time()
        ];
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        if (! empty($options['token'])) {
            $token = $options['token'];
            unset($options['token']);
        } else {
            $tokenInfo = $this->getAuthToken();
            $token = $tokenInfo['token'];
        }
        $this->setHeader('authToken', $token);

        $to = is_array($to) ? array_unique($to) : [$to];
        if (count($to) > 1) {
            return $this->pushMultiNotify($to, $message, $options);
        } else {
            $to = array_pop($to);
            return $this->pushSingleNotify($to, $message, $options);
        }
    }

    protected function pushSingleNotify($to, AbstractMessage $message, array $options = [])
    {
        $data = [
            'regId' => $to,
            'title' => $message->title,
            'content' => $message->content,
            'skipType' => 4,
            'skipContent' => $this->generateIntent($this->config->get('appPkgName'), $message->extra),
            'requestId' => $message->businessId,
            'notifyType' => 1,
        ];
        if ($message->callback) {
            $data['extra'] = [
                'callback' => $message->callback
            ];
            if ($message->callbackParam) {
                $data['extra']['callback.param'] = $message->callbackParam;
            }
        }
        $data = $this->mergeGatewayOptions($data, $message->gatewayOptions);
        $result = $this->postJson(
            sprintf('%s/%s', self::BASE_URL, self::SINGLE_PUSH_METHOD),
            $data,
            $this->getHeaders()
        );
        $this->assertFailure($result, 'Vivo推送失败');

        return $result['taskId'];
    }

    protected function pushMultiNotify($to, AbstractMessage $message, array $options = [])
    {
        $data = [
            'regIds' => $this->formatTo($to),
            'taskId' => $this->saveMessageToCloud($message),
            'requestId' => $message->businessId
        ];
        $result = $this->postJson(
            sprintf('%s/%s', self::BASE_URL, self::MULTI_PUSH_METHOD),
            $data,
            $this->getHeaders()
        );
        $this->assertFailure($result, 'Vivo推送失败');

        return $data['taskId'];
    }

    protected function saveMessageToCloud(AbstractMessage $message, array $options = [])
    {
        $data = [
            'title' => $message->title,
            'content' => $message->content,
            'skipType' => 4,
            'skipContent' => $this->generateIntent($this->config->get('appPkgName'), $message->extra),
            'requestId' => $message->businessId,
            'notifyType' => 1
        ];
        if ($message->callback) {
            $data['extra'] = [
                'callback' => $message->callback
            ];
            if ($message->callbackParam) {
                $data['extra']['callback.param'] = $message->callbackParam;
            }
        }
        $data = $this->mergeGatewayOptions($data, $message->gatewayOptions);
        $result = $this->postJson(
            sprintf('%s/%s', self::BASE_URL, self::SAVE_MESSAGE_METHOD),
            $data,
            $this->getHeaders()
        );
        $this->assertFailure($result, '保存推送消息至Vivo服务器失败');
        return $result['taskId'];
    }

    protected function getTimestamp()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    protected function generateSign($data)
    {
        $strToSign = implode('',[
            $this->config->get('appId'),
            $this->config->get('appKey'),
            $data['timestamp'],
            $this->config->get('appSecret')
        ]);
        return bin2hex(hash('md5', $strToSign, true));
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

    protected function assertFailure($result, $message)
    {
        if (!isset($result['result']) || $result['result'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '%s > [%s] %s',
                $message,
                isset($result['result']) ? $result['result'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
    }
}