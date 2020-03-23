<?php


namespace MingYuanYun\Push;



class Message extends AbstractMessage
{
    /**
     * Message constructor.
     *
     * @param array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $this->doProperty($property, $value);
            } else {
                $this->gatewayOptions[$property] = $value;
            }
        }
    }

    /**
     * @param $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        } elseif (array_key_exists($property, $this->gatewayOptions)) {
            return $this->gatewayOptions[$property];
        }
        return null;
    }

    protected function doProperty($name, $value)
    {
        switch ($name) {
            case 'title':
                return mb_substr($value, 0, 10);
            case 'subTitle':
                return $value ? mb_substr($value, 0, 10) : '';
            case 'content':
                $value = $value ? mb_substr($value, 0, 20) : '';
                if ($value && $value == $this->subTitle) {
                    return '';
                }
                return $value;
            case 'callbackParam':
                return $value ? mb_substr($value, 0, 50) : '';
            case 'notifyId':
                if (! preg_match('/^[0-9a-zA-Z_\-]{1,8}$/', $value)) {
                    return '';
                }
                return $value;
            case 'extra':
            case 'gatewayOptions':
                if (! is_array($value)) {
                    return [];
                }
                return $value;
            default:
                return $value;
        }
    }
}