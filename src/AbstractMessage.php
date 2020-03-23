<?php

namespace MingYuanYun\Push;


use MingYuanYun\Push\Contracts\MessageInterface;

abstract class AbstractMessage implements MessageInterface
{
    /**
     * @var string 消息标题
     */
    public $title;

    /**
     * @var string 消息副标题
     */
    public $subTitle;

    /**
     * @var string 消息内容
     */
    public $content;

    /**
     * @var array 业务自定义参数
     */
    public $extra;

    /**
     * @var string 消息ID
     */
    public $businessId;

    /**
     * @var string 角标
     */
    public $badge;

    /**
     * @var string 回执地址
     */
    public $callback;

    /**
     * @var string 回执参数
     */
    public $callbackParam;

    /**
     * @var string 消息聚合标签
     */
    public $notifyId;

    /**
     * @var array 厂商扩展参数
     */
    public $gatewayOptions;
}