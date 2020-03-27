<?php

namespace MingYuanYun\Push;


use Closure;
use MingYuanYun\Push\Contracts\GatewayInterface;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Exceptions\InvalidArgumentException;
use MingYuanYun\Push\Gateways\Gateway;
use MingYuanYun\Push\Support\Config;

class Push
{
    protected $config;

    protected $gatewayConfig;

    /**
     * @var Gateway
     */
    protected $gateway;

    protected $customGateways = [];

    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    public function setPusher($gateway)
    {
        $this->gatewayConfig = $this->config->get($gateway);
        $this->gateway = $this->createGateway($gateway);
    }

    public function getPusher()
    {
        return $this->gateway->getGatewayName();
    }

    public function getAuthToken()
    {
        return $this->gateway->getAuthToken();
    }

    public function pushNotice($to, $message, array $options = [])
    {
        $message = $this->formatMessage($message);
        $options = $this->checkOptions($options);

        return $this->gateway->pushNotice($to, $message, $options);
    }

    public function extend($name, Closure $callback)
    {
        $this->customGateways[$name] = $callback;

        return $this;
    }

    protected function formatMessage($message)
    {
        if (!($message instanceof AbstractMessage)) {
            if (!is_array($message)) {
                throw new InvalidArgumentException('无效的推送内容格式');
            }
            $message = new Message($message, $this->gateway->getGatewayName());
        }
        return $message;
    }

    protected function createGateway($name)
    {
        if (isset($this->customGateways[$name])) {
            $gateway = $this->makeCustomGateway($name);
        } else {
            $className = $this->formatGatewayClassName($name);
            $gateway = $this->makeGateway($className);
        }

        if (!($gateway instanceof GatewayInterface)) {
            throw new InvalidArgumentException(sprintf('Gateway "%s" not inherited from %s.', $name, GatewayInterface::class));
        }

        return $gateway;
    }

    protected function makeCustomGateway($name)
    {
        return call_user_func($this->customGateways[$name], $this->gatewayConfig);
    }

    protected function formatGatewayClassName($name)
    {
        if (class_exists($name)) {
            return $name;
        }
        if ('apple' == strtolower($name)) {
            $name = 'ios';
        } elseif ('apple-token' == strtolower($name)) {
            $name = 'ios-token';
        }
        $nameArr = preg_split("/(\-|_| )/", $name);
        $gateway = implode('', array_map('ucfirst', $nameArr));

        return __NAMESPACE__."\\Gateways\\{$gateway}Gateway";
    }

    protected function makeGateway($gateway)
    {
        if (!class_exists($gateway)) {
            throw new GatewayErrorException(sprintf('Gateway "%s" not exists.', $gateway));
        }

        return new $gateway($this->gatewayConfig);
    }

    protected function checkOptions(array $options)
    {
        return array_map('trim', $options);
    }
}