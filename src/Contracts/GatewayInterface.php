<?php


namespace MingYuanYun\Push\Contracts;


use MingYuanYun\Push\AbstractMessage;


interface GatewayInterface
{
    public function getName();

    public function getAuthToken();

    public function pushNotice($to, AbstractMessage $message, array $options = []);
}