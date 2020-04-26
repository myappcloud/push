<?php


namespace MingYuanYun\Push\Gateways;

use MingYuanYun\Push\Contracts\GatewayInterface;
use MingYuanYun\Push\Exceptions\InvalidArgumentException;
use MingYuanYun\Push\Support\Config;
use MingYuanYun\Push\Support\ArrayHelper;


abstract class Gateway implements GatewayInterface
{
    const DEFAULT_TIMEOUT = 5.0;

    const GATEWAY_NAME = 'default';

    protected $config;

    protected $maxTokens;

    protected $timeout;

    protected $headers = [];


    /**
     * Gateway constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * Return timeout.
     *
     * @return int|mixed
     */
    public function getTimeout()
    {
        return $this->timeout ?: $this->config->get('timeout', self::DEFAULT_TIMEOUT);
    }

    /**
     * Set timeout.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = floatval($timeout);

        return $this;
    }

    /**
     * @return \MingYuanYun\Push\Support\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param \MingYuanYun\Push\Support\Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return \strtolower(str_replace([__NAMESPACE__.'\\', 'Gateway'], '', \get_class($this)));
    }

    public function getGatewayName()
    {
        return static::GATEWAY_NAME;
    }

    protected function mergeGatewayOptions($payload, $gatewayOptions)
    {
        $gatewayName = $this->getGatewayName();
        if (! $gatewayOptions || ! is_array($gatewayOptions) || ! array_key_exists($gatewayName, $gatewayOptions)) {
            return $payload;
        }

        return ArrayHelper::merge($payload, $gatewayOptions[$gatewayName]);
    }

    public function generateIntent($appPkgName, $extra)
    {
        if (is_null($extra)) {
            $extra = [];
        }
        return sprintf(
            'mic_scheme://%s/push?%s#Intent;launchFlags=0x24000000;end',
            $appPkgName,
            urlencode($this->buildQuery($extra))
        );
    }

    protected function buildQuery(array $extra, $slash = '&')
    {
        $extraStr = '';
        foreach ($extra as $key => $value) {
            $extraStr .= "{$slash}{$key}={$value}";
        }
        return ltrim($extraStr, $slash);
    }

    protected function getHeaders()
    {
        return $this->headers;
    }

    protected function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    protected function checkMaxToken($tokens)
    {
        if (is_array($tokens) && count($tokens) > $this->maxTokens)
        {
            throw new InvalidArgumentException(sprintf(
                '超过%s推送通道单次最大设备数',
                $this->getName()
            ));
        }
    }
}