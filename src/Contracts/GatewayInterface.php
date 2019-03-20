<?php


namespace MingYuanYun\Push\Contracts;



interface GatewayInterface
{
    public function getName();

    public function getAuthToken();

    public function pushNotice($to, MessageInterface $message, array $options = []);
}