<?php

namespace MingYuanYun\Push;


use MingYuanYun\Push\Contracts\MessageInterface;

abstract class AbstractMessage implements MessageInterface
{
    public $title;

    public $subTitle;

    public $content;

    public $extra;

    public $businessId;

    public $badge;

    public $callback;

    public $callbackParam;
}