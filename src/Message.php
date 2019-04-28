<?php


namespace mingyuanyun\push;


use MingYuanYun\Push\Contracts\MessageInterface;

class Message implements MessageInterface
{
    protected $title;

    protected $subTitle;

    protected $content;

    protected $extra;

    protected $businessId;

    protected $badge;

    protected $callback;

    protected $callbackParam;


    /**
     * Message constructor.
     *
     * @param array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * @param $property
     *
     * @return string
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}