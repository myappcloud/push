<?php


namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\Contracts\MessageInterface;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class OppoGateway extends Gateway
{
    use HasHttpRequest;

    // http://storepic.oppomobile.com/openplat/resource/201904/03/OPPO推送平台服务端API-V1.6.pdf

    const BASE_URL = 'https://api.push.oppomobile.com/server/v1';

    const AUTH_METHOD = 'auth';

    const SAVE_MESSAGE_CONTENT_METHOD = 'message/notification/save_message_content';

    const BROADCAST_METHOD = 'message/notification/broadcast';

    const PUSH_METHOD = 'message/notification/unicast';

    const OK_CODE = 0;

    protected $maxTokens = 1000;

    protected $headers = [
        'Content-Type' => 'application/x-www-form-urlencoded',
    ];


    public function getAuthToken()
    {
        $data = [
            'app_key' => $this->config->get('appKey'),
            'timestamp' => $this->getTimestamp()
        ];
        $data['sign'] = $this->generateSign($data);

        $result = $this->post(
            sprintf('%s/%s', self::BASE_URL, self::AUTH_METHOD),
            $data,
            $this->getHeaders()
        );
        $this->assertFailure($result, '获取Oppo推送token失败');

        $createdTime = (int) ($result['data']['create_time'] / 1000);
        return [
            'token' => $result['data']['auth_token'],
            'expires' => strtotime('+1day', $createdTime) - time()
        ];
    }

    public function pushNotice($to, MessageInterface $message, array $options = [])
    {
        if (isset($options['token'])) {
            $token = $options['token'];
            unset($options['token']);
        } else {
            $tokenInfo = $this->getAuthToken();
            $token = $tokenInfo['token'];
        }
        $messageData = [
            'title' => $message->title,
            'sub_title' => $message->subTitle ? $message->subTitle : '',
            'content' => $message->content,
            'click_action_type' => 5,
            'click_action_url' => $this->generateIntent($this->config->get('appPkgName'), $message->extra)
        ];
        if ($message->callback) {
            $messageData['call_back_url'] = $message->callback;
            if ($message->callbackParam) {
                $messageData['call_back_parameter'] = $message->callbackParam;
            }
        }
        $to = is_array($to) ? array_unique($to) : [$to];
        if (count($to) > 1) {
            return $this->pushBroadcast($token, $to, $messageData);
        } else {
            $to = array_pop($to);
            return $this->pushSingle($token, $to, $messageData);
        }
    }

    protected function pushSingle($authToken, $to, $message)
    {
        $data = [
            'message' => json_encode([
                'target_type' => 2,
                'target_value' => $to,
                'notification' => $message
            ]),
            'auth_token' => $authToken
        ];
        $result = $this->post(
            sprintf('%s/%s', self::BASE_URL, self::PUSH_METHOD),
            $data,
            $this->getHeaders()
        );
        $this->assertFailure($result, 'Oppo推送失败');
        return $result['data']['messageId'];
    }

    protected function saveMessageToCloud($authToken, array $message)
    {
        $message['auth_token'] = $authToken;
        $result = $this->post(
            sprintf('%s/%s', self::BASE_URL, self::SAVE_MESSAGE_CONTENT_METHOD),
            $message,
            $this->getHeaders()
        );
        $this->assertFailure($result, 'Oppo多推时保存消息至Oppo服务器失败');
        return $result['data']['message_id'];
    }

    protected function pushBroadcast($authToken, $to, $message)
    {
        $messageId = $this->saveMessageToCloud($authToken, $message);
        $data = [
            'message_id' => $messageId,
            'target_type' => 2,
            'target_value' => $this->formatTo($to),
            'auth_token' => $authToken
        ];
        $result = $this->post(
            sprintf('%s/%s', self::BASE_URL, self::BROADCAST_METHOD),
            $data,
            $this->getHeaders()
        );
        $this->assertFailure($result, 'Oppo多推失败');
        return $this->parseBroadcastResult($result);
    }

    protected function parseBroadcastResult(array $result)
    {
        $data = $result['data'];
        $messageId = $data['message_id'];
        unset($data['message_id'], $data['task_id'], $data['status']);
        if (count($data) > 0) {
            throw new GatewayErrorException(sprintf(
                'Oppo多推时部分设备推送失败 > %s',
                json_encode($data)
            ));
        }
        return $messageId;
    }

    protected function getTimestamp()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    protected function generateSign($data)
    {
        $strToSign = implode('',[
            $this->config->get('appKey'),
            $data['timestamp'],
            $this->config->get('masterSecret')
        ]);
        return bin2hex(hash('sha256', $strToSign, true));
    }

    protected function formatTo($to)
    {
        if (!is_array($to)) {
            $to = [$to];
        } else {
            $this->checkMaxToken($to);
        }
        return implode(';', $to);
    }

    protected function assertFailure($result, $message)
    {
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '%s > [%s] %s',
                $message,
                isset($result['code']) ? $result['code'] : '-99',
                isset($result['message']) ? $result['message'] : '未知异常'
            ));
        }
    }
}