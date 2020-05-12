<?php

namespace MingYuanYun\Push\Gateways;


use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\InvalidArgumentException;
use MingYuanYun\Push\Support\ArrayHelper;

class IosTokenGateway extends Gateway
{
    // https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/establishing_a_token-based_connection_to_apns

    const ALGORITHM = 'ES256';

    const GATEWAY_NAME = 'ios-token';

    protected $maxTokens = 100;

    public function getAuthToken()
    {
        $token = $this->generateJwt();
        return [
            'token' => $token,
            'expires' => strtotime('+ 50 minutes') - time()
        ];
    }

    protected function generateJwt()
    {
        $payload = [
            'iss' => $this->config->get('teamId'),
            'iat' => time()
        ];
        $header = [
            'alg' => static::ALGORITHM,
            'kid' => $this->config->get('keyId')
        ];
        $secretContent = $this->config->get('secretContent');
        if (! $secretContent) {
            $secretFile = $this->config->get('secretFile');
            if (!file_exists($secretFile)) {
                throw new InvalidArgumentException('无效的推送密钥证书地址 > ' . $secretFile);
            }
            $secretContent = file_get_contents($secretFile);
        }

        return JWT::encode($payload, $secretContent, static::ALGORITHM, null, $header);
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        $to = $this->formatTo($to);
        if (!$to) {
            throw new InvalidArgumentException('无有效的设备token');
        }

        if (isset($options['token'])) {
            $token = $options['token'];
            unset($options['token']);
        } else {
            $tokenInfo = $this->getAuthToken();
            $token = $tokenInfo['token'];
        }

        $header = [
            'authorization' => sprintf('bearer %s', $token),
            'apns-topic' => $this->config->get('bundleId'),
            'content-type' => 'application/json',
            'apns-id' => $message->businessId,
            'apns-collapse-id' => $message->notifyId,
        ];
        $payload = $this->createPayload($message);

        $callback = [];
        if ($message->callback) {
            $callback['url'] = $message->callback;
            if ($message->callbackParam) {
                $callback['params'] = $message->callbackParam;
            }
        }
        $this->_push($to, $payload, $header, $callback);
    }

    protected function createPayload(AbstractMessage $message)
    {
        $payload = [
            'aps' => [
                'alert' => [
                    'title' => $message->title,
                    'subtitle' => $message->subTitle ? $message->subTitle : '',
                    'body' => $message->content,
                ],
                'sound' => 'default',
                'badge' => $message->badge ? intval($message->badge) : 0,
            ],
        ];
        if ($message->extra && is_array($message->extra)) {
            $payload = array_merge($payload, $message->extra);
        }
        $payload = $this->mergeGatewayOptions($payload, $message->gatewayOptions);
        if (ArrayHelper::getValue($payload, 'aps.mutable-content') == 1) {
            unset($payload['aps']['sound']);
        }
        return json_encode($payload);
    }

    private function getPushUrl()
    {
        $isSandBox = $this->config->get('isSandBox');
        if ($isSandBox) {
            return 'https://api.sandbox.push.apple.com/3/device/';
        } else {
            return 'https://api.push.apple.com/3/device/';
        }
    }

    protected function _push($deviceTokens, $payload, $header, $callback = [])
    {
        $client = new Client();

        $requests = function ($deviceTokens, $payload, $header) {
            $baseUrl = $this->getPushUrl();
            foreach ($deviceTokens as $deviceToken) {
                yield new Request(
                    'POST',
                    $baseUrl . $deviceToken,
                    $header,
                    $payload,
                    2.0
                );
            }
        };

        $pool = new Pool($client, $requests($deviceTokens, $payload, $header), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use ($deviceTokens, $callback, $payload) {
                $apnsIds = $response->getHeader('apns-id');
                $apnsId = array_pop($apnsIds);
                $deviceToken = $deviceTokens[$index];
                $result = [
                    'deviceToken' => $deviceToken,
                    'status' => 'success',
                    'taskId' => $apnsId
                ];
                $this->notifyCallback($callback, $result, $payload);
            },
            'rejected' => function ($reason, $index) use ($deviceTokens, $callback, $payload) {
                $errorMsg = $reason->getMessage();
                $deviceToken = $deviceTokens[$index];
                if (preg_match_all('/(\d{3} [^`]+).*"reason":"(.+)"/is', $errorMsg, $matches)) {
                    $msg = sprintf('%s for %s', $matches[1][0], $matches[2][0]);
                } else {
                    $msg = $errorMsg;
                }
                $result = [
                    'deviceToken' => $deviceToken,
                    'status' => 'fail',
                    'reason' => $msg
                ];
                $this->notifyCallback($callback, $result, $payload);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }

    protected function notifyCallback($callback, $data, $payload)
    {
        if (!$callback) {
            return;
        }
        if (isset($callback['params'])) {
            $data['params'] = $callback['params'];
        }
        $payload = json_decode($payload, true);
        $data['businessId'] = $payload['aps']['alert']['apns-collapse-id'];

        $client = new Client();
        $promise = $client->postAsync($callback['url'], ['json' => $data]);
        $promise->wait();
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