<?php


namespace MingYuanYun\Push;



use MingYuanYun\Push\Support\ArrayHelper;

class Message extends AbstractMessage
{
    /**
     * property limit for each channel
     *
     * @var array
     */
    private $propertyLimit = [
        'title' => [
            'oppo' => 50,
            'vivo' => 20,
            'meizu' => 32,
        ],
        'subTitle' => [
            'oppo' => 10,
        ],
        'content' => [
            'oppo' => 200,
            'vivo' => 50,
            'meizu' => 100,
        ],
        'callback' => [
            'meizu' => 128,
            'oppo' => 200,
            'vivo' => 128,
        ],
        'callbackParam' => [
            'xiaomi' => 64,
            'meizu' => 64,
            'oppo' => 50,
            'vivo' => 64,
        ],
    ];

    /**
     * Message constructor.
     *
     * @param array  $attributes
     * @param string $gatewayName
     */
    public function __construct(array $attributes = [], $gatewayName)
    {
        if (! $this->gatewayOptions) {
            $this->gatewayOptions = [];
        }

        foreach ($attributes as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $this->doProperty($property, $value, $gatewayName);
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

    protected function doProperty($name, $value, $gatewayName)
    {
        $limit = ArrayHelper::getValue($this->propertyLimit, "{$name}.{$gatewayName}");
        switch ($name) {
            case 'title':
                return $limit ? mb_substr($value, 0, $limit) : $value;
            case 'subTitle':
                return $value ? ($limit ? mb_substr($value, 0, $limit) : $value) : '';
            case 'content':
                $value = $value ? ($limit ? mb_substr($value, 0, $limit) : $value) : '';
                if ($value && $value == $this->subTitle) {
                    return '';
                }
                return $value;
            case 'callback':
                return $value ? ($limit ? mb_substr($value, 0, $limit) : $value) : '';
            case 'callbackParam':
                return $value ? ($limit ? mb_substr($value, 0, $limit) : $value) : '';
            case 'notifyId':
                if (! preg_match('/^[0-9a-zA-Z_\-]{1,8}$/', $value)) {
                    return '';
                }
                return $value;
            case 'badge':
                return $this->checkBadge($gatewayName, $value);
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

    private function checkBadge($gatewayName, $badge)
    {
        if (! $badge) {
            return $badge;
        }
        if ($gatewayName != 'huawei-v2') {
            if (preg_match('/(\d+)/', $badge, $match)) {
                return $match[0];
            }
        }
        return $badge;
    }
}